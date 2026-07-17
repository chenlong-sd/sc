<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

class OptionField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    protected array $options = [];
    protected ?array $remoteOptions = null;
    protected array $remoteOptionDependencies = [];
    protected bool $remoteOptionsClearOnChange = true;
    protected ?string $optionsStatePath = null;
    protected ?JsExpression $optionsExpression = null;
    protected ?JsExpression $computedOptions = null;
    protected array $linkageUpdates = [];
    protected bool $linkageClearOnEmpty = true;
    protected bool $defaultedByMultiple = false;

    public function __construct(string $name, string $label, FieldType $type = FieldType::SELECT)
    {
        parent::__construct($name, $label, $type);

        if ($type === FieldType::SELECT) {
            $this->prop('filterable', true);
        }
    }

    /**
     * 设置静态选项列表，支持 value => label 或完整选项数组格式。
     * 完整选项数组至少包含 `value` / `label`，也可附带 `disabled` 或其它扩展字段，
     * 这些扩展字段可在 linkageUpdate() 中通过 "@option.xxx" 读取。
     *
     * @param array $options 选项列表。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('status', '状态')->options([1 => '启用', 0 => '停用'])`
     */
    public function options(array $options): static
    {
        $this->options = [];
        $this->optionsStatePath = null;
        $this->optionsExpression = null;
        $this->computedOptions = null;

        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label) && array_key_exists('label', $label)) {
                $this->options[] = $label;
                continue;
            }

            $this->options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $this;
    }

    /**
     * 使用页面运行时 state 中的数组作为选项源。
     * path 会交给前端 `getState(path, [])` 读取，适合以下场景：
     * - 选项已经放在 `Pages::state()` 写入的页面级 state 中
     * - 选项已经放在 `Forms::state()` 写入的表单级 state 中
     * - 运行时希望通过 `setState()` / `setFieldOptions()` 继续更新同一份 state
     *
     * 路径是相对于 pageState 根对象的绝对路径：
     * - 页面级 state 直接写，例如 `statusOptions`
     * - 表单级 state 默认挂在 `forms.{scope}.xxx`，例如 `forms.article-form.statusOptions`
     *
     * 这个来源只做“按路径取值”，不执行表达式；如果需要前端实时计算，请改用
     * `optionsExpression()` 或 `computedOptions()`。
     *
     * @param string $path 页面 state 路径，例如 statusOptions 或 forms.article-form.statusOptions。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::radio('status', '状态')->optionsState('statusOptions')`
     * - `Fields::select('project_id', '项目')->optionsState('forms.issue-form.project')`
     */
    public function optionsState(string $path): static
    {
        $path = trim($path);
        if ($path === '') {
            return $this;
        }

        $this->optionsStatePath = $path;
        $this->optionsExpression = null;
        $this->computedOptions = null;

        return $this;
    }

    /**
     * 使用前端表达式作为选项源。
     * 表达式应返回标准选项数组，适合直接绑定已有 Vue 响应式变量，或做一层轻量过滤。
     *
     * 与 `computedOptions()` 不同，这里传入的是“表达式本身”，不是接收 context 的函数。
     * 运行时会在当前页面上下文中直接注入以下变量供表达式使用：
     * - model: 当前字段所在的局部模型
     * - form: 当前表单完整模型
     * - state / pageState: 当前页面运行时 state，当前实现里两者是同一份对象
     * - scope: 当前表单 scope / key
     * - vm: 当前页面根实例
     * - fieldName: 当前字段路径
     * - getState(path, fallback): 读取页面 state
     * - setState(path, value): 写入页面 state
     * 字段渲染在弹窗 body 内时，表达式还可直接读取外层模板变量 `dialogRow`；
     * `dialogRow` 只表示来源表格行上下文，不属于表单 `model`，不会随表单提交。
     *
     * 如果逻辑已经复杂到更适合写成函数，或你希望显式解构 context，请改用
     * `computedOptions()`。
     *
     * @param string|JsExpression $expression 前端表达式，例如 `pageState.statusOptions`。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::radio('status')->optionsExpression("pageState.statusOptions")`
     * - `Fields::select('project_id')->optionsExpression("(pageState.forms?.[scope]?.project || []).filter(item => item.business_type_id === form.business_type_id)")`
     * - `Fields::radio('result')->optionsExpression("dialogRow?.business_type?.scene == 1 ? yesOptions : noOptions")`
     */
    public function optionsExpression(string|JsExpression $expression): static
    {
        $expression = JsExpression::ensure($expression);
        if (trim($expression->expression()) === '') {
            return $this;
        }

        $this->optionsExpression = $expression;
        $this->optionsStatePath = null;
        $this->computedOptions = null;

        return $this;
    }

    /**
     * 使用前端计算函数动态返回选项。
     * 函数会收到一个 context 对象，当前可用字段如下：
     * - model: 当前字段所在的“局部模型”。
     *   顶层字段时通常等于整个 form；嵌套对象字段时是其父对象；
     *   数组行字段时通常是当前行数据，适合读取同级字段。
     * - form: 当前表单的完整模型根对象，可读取当前表单任意字段。
     * - state: 当前页面运行时 state。
     *   包含 `Pages::state()` 写入的数据，也包含 `Forms::state()` 写入的数据。
     *   其中表单专属 state 默认挂在 `state.forms.{scope}` 下。
     * - pageState: `state` 的语义化别名，当前实现里两者指向同一份对象。
     *   当你想强调“页面级运行时状态”时可使用这个名字。
     * - scope: 当前表单 scope / key，例如 `article-form`。
     *   若当前渲染上下文没有显式 scope，可能为 `null`。
     * - vm: 当前页面运行时 Vue 实例 / 根 VM。
     *   适合访问公开的运行时方法或页面级能力；仅做选项计算时通常优先使用
     *   `model` / `form` / `state` 即可。
     *
     * 另外，运行时还会额外注入以下辅助字段：
     * - fieldName: 当前字段路径，例如 `status`、`profile.dept_id`。
     * - getState(path, fallback): 读取页面 state 的辅助方法。
     * - setState(path, value): 写入页面 state 的辅助方法。
     * 字段渲染在弹窗 body 内时，resolver 函数体还可直接读取外层模板变量 `dialogRow`；
     * 注意它不是 context 对象字段，不能通过 `({ dialogRow }) => ...` 解构获取。
     *
     * @param string|JsExpression $resolver 返回选项数组的表达式或函数。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::radio('status')->computedOptions('({ model, state }) => model.type === 1 ? state.articleOptions : state.videoOptions')`
     * - `Fields::select('project_id')->computedOptions('({ form, pageState, scope }) => (pageState.forms?.[scope]?.project || []).filter(item => item.business_type_id === form.business_type_id)')`
     * - `Fields::radio('result')->computedOptions('({ state }) => dialogRow?.status == 1 ? state.yesOptions : state.noOptions')`
     */
    public function computedOptions(string|JsExpression $resolver): static
    {
        $resolver = JsExpression::ensure($resolver);
        if (trim($resolver->expression()) === '') {
            return $this;
        }

        $this->computedOptions = $resolver;
        $this->optionsStatePath = null;
        $this->optionsExpression = null;

        return $this;
    }

    /**
     * 将 select 切换为多选下拉。
     * 开启后会透传 Element Plus 的 `multiple` 属性，并把空默认值初始化为数组。
     *
     * @param bool $enable 是否开启多选，默认值为 true。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('roles', '角色')->multiple()->options($roleOptions)`
     */
    public function multiple(bool $enable = true): static
    {
        if ($this->type() !== FieldType::SELECT) {
            return $this;
        }

        $this->prop('multiple', $enable);

        if ($enable && !is_array($this->default)) {
            $this->default = [];
            $this->defaultedByMultiple = true;
        }

        if (!$enable && $this->defaultedByMultiple && $this->default === []) {
            $this->default = null;
            $this->defaultedByMultiple = false;
        }

        return $this;
    }

    /**
     * 多选 select 的默认值必须是数组，避免前端 el-select 多选模型类型不匹配。
     */
    public function default(mixed $default): static
    {
        $this->defaultedByMultiple = false;

        if ($this->isMultipleSelect() && !is_array($default)) {
            $default = [];
        }

        return parent::default($default);
    }

    /**
     * 配置远端选项加载接口及值/标签字段映射。
     * - `params` 中以 "@" 开头的顶层值会从当前表单 model 读取，
     * 例如 `['dept_id' => "@dept_id"]`。返回列表会按 valueField/labelField 归一化成标准选项。
     * 这里默认只有当前作用域下的 form model 可用，不会注入 row / tableKey / listKey / vm。
     *
     * @param string $url 远端选项接口地址。
     * @param string $valueField 结果中作为值的字段名，默认值为 id。
     * @param string $labelField 结果中作为文案的字段名，默认值为 name。
     * @param array $params 远端请求参数。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('dept_id', '部门')->remoteOptions('/admin/dept/options', 'id', 'name')`
     */
    public function remoteOptions(
        string|\Stringable $url,
        string $valueField = 'id',
        string $labelField = 'name',
        array $params = []
    ): static {
        $this->remoteOptions = [
            'url' => (string)$url,
            'method' => 'get',
            'valueField' => $valueField,
            'labelField' => $labelField,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * 开启下拉远程搜索，兼容旧版 select remoteSearch() 的默认请求格式。
     * 用户输入会作为 `search.search[$searchField]` 发送，默认使用 like 查询；
     * 字段已有值但选项为空时，会用 `$haveDefaultSearchField` 回查当前选中项。
     *
     * @param string|\Stringable $url 远程搜索接口地址。
     * @param string|null $searchField 搜索字段，同时作为默认展示字段；不传时使用当前字段名。
     * @param string|null $haveDefaultSearchField 当前表单已有值时回查的字段，默认 id。
     * @param string|null $afterSearchHandle 搜索完成后的 JS 处理代码，运行时可读 data/options/ctx。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('user_id', '用户')->remoteSearch('/admin/user/listsData', 'name')`
     */
    public function remoteSearch(
        string|\Stringable $url,
        ?string $searchField = null,
        ?string $haveDefaultSearchField = null,
        ?string $afterSearchHandle = null
    ): static {
        $field = $this->normalizeRemoteSearchField($searchField ?: $this->name());
        [$labelField, $valueField] = $this->resolveRemoteSearchLabelAndValue($field, $haveDefaultSearchField);

        $this->remoteOptions($url, $valueField, $labelField);
        $this->remoteOptions['remoteSearch'] = [
            'enabled' => true,
            'searchField' => $field,
            'defaultSearchField' => $valueField,
            'searchType' => 'like',
            'page' => 1,
            'pageSize' => 20,
            'afterSearchHandle' => $afterSearchHandle,
        ];

        return $this;
    }

    /**
     * 设置远端选项加载请求方法。
     * 默认是 `get`，也可改成 `post` / `put` 等。
     *
     * @param string $method 请求方法。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('dept_id', '部门')->remoteOptionsMethod('post')`
     */
    public function remoteOptionsMethod(string $method): static
    {
        if ($this->remoteOptions === null) {
            $this->remoteOptions = [];
        }

        $this->remoteOptions['method'] = strtolower($method);

        return $this;
    }

    /**
     * 声明远端选项依赖的表单字段，依赖变更时可重新拉取。
     * 字段路径相对当前表单作用域解析；在数组行里会自动映射到当前行上下文。
     * 依赖字段为空时，请求不会触发。
     *
     * @param string ...$fields 依赖字段路径。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('city_id', '城市')->remoteOptionsDependsOn('province_id')`
     */
    public function remoteOptionsDependsOn(string ...$fields): static
    {
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '' || in_array($field, $this->remoteOptionDependencies, true)) {
                continue;
            }

            $this->remoteOptionDependencies[] = $field;
        }

        return $this;
    }

    /**
     * 控制依赖字段变化时是否清空当前选中值。
     * 默认开启，避免旧值与新选项集不一致。
     *
     * @param bool $clear 是否自动清空，默认值为 true。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('city_id', '城市')->remoteOptionsClearOnChange(false)`
     */
    public function remoteOptionsClearOnChange(bool $clear = true): static
    {
        $this->remoteOptionsClearOnChange = $clear;

        return $this;
    }

    /**
     * 配置联动更新，把当前选项的值同步到其它字段。
     * 常用模板：
     * - "@value": 当前选中值
     * - "@label": 当前选中文案
     * - "@model.xxx": 当前表单其它字段
     * - "@option.xxx": 当前选项对象上的扩展字段
     * 也支持把这些 token 混入普通字符串，例如 "部门：@label"。
     *
     * 若传 JsExpression，运行时只接收一个 context 对象，包含：
     * - scope: 当前表单作用域
     * - fieldName: 当前字段名
     * - value: 当前选中值
     * - option: 当前命中的标准化选项对象
     * - model: 当前作用域下的表单模型
     * 当前联动主要用于 select/radio/cascader 的 change 行为。
     *
     * @param string $targetField 要更新的目标字段路径。
     * @param string|JsExpression $valueTemplate 更新模板，默认值为 @label。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - Fields::select('dept_id', '部门')->linkageUpdate('dept_name', "@label")
     */
    public function linkageUpdate(string $targetField, string|JsExpression $valueTemplate = '@label'): static
    {
        $this->linkageUpdates[$targetField] = $valueTemplate;

        return $this;
    }

    /**
     * 批量配置联动更新规则。
     * 每条规则的模板语义与 linkageUpdate() 完全一致。
     *
     * @param array $updates 联动更新规则。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('dept_id', '部门')->linkageUpdates(['dept_name' => '@label', 'dept_code' => '@option.code'])`
     */
    public function linkageUpdates(array $updates): static
    {
        foreach ($updates as $targetField => $valueTemplate) {
            if (!is_string($targetField)) {
                continue;
            }

            if (!is_string($valueTemplate) && !$valueTemplate instanceof JsExpression) {
                continue;
            }

            $this->linkageUpdate($targetField, $valueTemplate);
        }

        return $this;
    }

    /**
     * 控制选项清空时是否同步清空联动目标字段。
     * 默认开启，适合“选择上级后自动填充下级字段”的场景。
     *
     * @param bool $clear 是否清空联动字段，默认值为 true。
     * @return static 当前选项字段实例。
     *
     * 示例：
     * - `Fields::select('dept_id', '部门')->linkageClearOnEmpty(false)`
     */
    public function linkageClearOnEmpty(bool $clear = true): static
    {
        $this->linkageClearOnEmpty = $clear;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionsStatePath(): ?string
    {
        return $this->optionsStatePath;
    }

    public function getOptionsExpression(): ?JsExpression
    {
        return $this->optionsExpression;
    }

    public function getComputedOptions(): ?JsExpression
    {
        return $this->computedOptions;
    }

    public function hasRemoteOptions(): bool
    {
        return $this->remoteOptions !== null;
    }

    public function getRemoteOptions(): ?array
    {
        if ($this->remoteOptions === null) {
            return null;
        }

        return array_merge($this->remoteOptions, [
            'dependencies' => $this->getRemoteOptionDependencies(),
            'clearOnChange' => $this->remoteOptionsClearOnChange,
        ]);
    }

    public function hasRemoteSearch(): bool
    {
        return ($this->remoteOptions['remoteSearch']['enabled'] ?? false) === true;
    }

    public function hasLinkageUpdates(): bool
    {
        return $this->linkageUpdates !== [];
    }

    public function getLinkageConfig(): ?array
    {
        if (!$this->hasLinkageUpdates()) {
            return null;
        }

        return [
            'updates' => $this->linkageUpdates,
            'clearOnEmpty' => $this->linkageClearOnEmpty,
        ];
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        if ($this->type() === FieldType::CHECKBOX) {
            return [];
        }

        return $this->isMultipleSelect() ? [] : null;
    }

    protected function defaultPromptPrefix(): string
    {
        return '请选择';
    }

    protected function validationPromptPrefix(): string
    {
        return '请选择';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return 'change';
    }

    private function getRemoteOptionDependencies(): array
    {
        $dependencies = $this->remoteOptionDependencies;

        foreach (($this->remoteOptions['params'] ?? []) as $value) {
            $this->collectRemoteDependenciesFromValue($value, $dependencies);
        }

        return array_values(array_unique($dependencies));
    }

    private function collectRemoteDependenciesFromValue(mixed $value, array &$dependencies): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectRemoteDependenciesFromValue($item, $dependencies);
            }

            return;
        }

        if (!is_string($value) || !str_starts_with($value, '@')) {
            return;
        }

        $field = trim(substr($value, 1));
        if ($field !== '') {
            $dependencies[] = $field;
        }
    }

    private function normalizeRemoteSearchField(string $field): string
    {
        $field = trim($field);

        return $field === '' ? $this->name() : $field;
    }

    private function resolveRemoteSearchLabelAndValue(string $field, ?string $defaultSearchField): array
    {
        $labelSource = str_contains($field, '&') ? explode('&', $field)[0] : $field;
        $fields = explode('.', $labelSource);
        $labelField = count($fields) === 2 ? $fields[1] : $fields[0];
        $valueField = $defaultSearchField ?: (count($fields) === 2 ? $fields[0] . '.id' : 'id');

        return [$labelField, $valueField];
    }

    private function isMultipleSelect(): bool
    {
        if ($this->type() !== FieldType::SELECT) {
            return false;
        }

        $multiple = $this->getProps()['multiple'] ?? null;

        return $multiple !== null && $multiple !== false && $multiple !== 'false';
    }
}
