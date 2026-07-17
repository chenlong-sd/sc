<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasFormTableColumnAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\ConditionalFormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

abstract class Field implements FormNode, ConditionalFormNode
{
    use HasFormTableColumnAttributes;
    use HasSpan;

    protected mixed $default = null;
    protected array $props = [];
    protected string $helpText = '';
    protected bool $visible = true;
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected array $suffixActions = [];
    protected string|AbstractHtmlElement|null $suffixContent = null;
    protected ?JsExpression $visibleWhen = null;
    protected ?JsExpression $disabledWhen = null;
    protected ?JsExpression $readonlyWhen = null;
    protected ?string $labelWidth = null;
    protected ?array $searchConfig = null;
    protected bool $searchEnabled = true;
    /** @var array<string, array<int, JsExpression>> */
    protected array $eventHandlers = [];

    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly FieldType $type
    ) {
    }

    /**
     * 设置字段默认值，在新建表单或未加载远端数据时生效。
     *
     * @param mixed $default 默认值。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->default('默认标题')`
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * 设置单个底层组件属性，适合透传 Element Plus 原生属性。
     * 属性名会原样输出到最终组件上；需要动态绑定时请自行带上 ":" / "@" 前缀，
     * 例如 ":min"、"@change"。
     * 若属性名以 ":" 开头：
     * - 传字符串时按原样作为前端表达式输出
     * - 传数组/布尔/数字/null 时会自动转成 JS 字面量
     * 字段渲染在弹窗 body 内时，动态属性表达式也可直接读取 `dialogRow`；
     * `dialogRow` 只表示来源表格行上下文，不属于表单 `model`，不会随表单提交。
     *
     * @param string $name 属性名，可带 ":" 或 "@" 前缀。
     * @param mixed $value 属性值。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->prop('clearable', true)`
     * - `Fields::text('title', '标题')->prop(':disabled', 'dialogRow?.status == 1')`
     */
    public function prop(string $name, mixed $value): static
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }

        if ($value === null || (!str_starts_with($name, ':') && $value === false)) {
            unset($this->props[$name]);

            return $this;
        }

        if (!str_starts_with($name, ':') && $value === true) {
            $value = '';
        }

        if (($name === 'class' || $name === 'style') && is_string($value)) {
            $value = $this->mergeFieldPropString($name, $value);
        }

        $this->props[$name] = $value;

        return $this;
    }

    /**
     * 批量设置底层组件属性。
     * 规则与 prop() 一致：键名按原样输出，动态属性请自行写成 ":prop" / "@event"；
     * 其中 ":" 开头属性的数组/布尔/数字/null 值会自动按 JS 字面量处理。
     * 字段渲染在弹窗 body 内时，动态属性表达式也可直接读取 `dialogRow`。
     *
     * @param array $props 要批量合并的属性。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->props(['clearable' => true, 'maxlength' => 100])`
     * - `Fields::text('title', '标题')->props([':disabled' => 'dialogRow?.status == 1'])`
     */
    public function props(array $props): static
    {
        foreach ($props as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $this->prop($name, $value);
        }

        return $this;
    }

    /**
     * 设置字段组件根节点的单个属性。
     * 这是 prop() 的语义化别名，便于与 Blocks / 布局节点 / 表单结构节点保持统一写法。
     * 属性会直接透传到字段实际渲染的组件根节点，不作用于外层 form-item。
     * 动态属性表达式上下文与 prop() 一致，弹窗 body 内可读取 `dialogRow`。
     *
     * @param string $name 属性名，可带 `":prop"` 或 `"@event"` 前缀。
     * @param mixed $value 属性值。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->attr(':maxlength', 50)`
     */
    public function attr(string $name, mixed $value = ''): static
    {
        return $this->prop($name, $value);
    }

    /**
     * 批量设置字段组件根节点属性。
     * 这是 props() 的语义化别名，便于在使用侧统一使用 attrs 风格 API。
     * 动态属性表达式上下文与 props() 一致，弹窗 body 内可读取 `dialogRow`。
     *
     * @param array $attributes 属性集合。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->attrs(['clearable' => true, ':maxlength' => 50])`
     */
    public function attrs(array $attributes): static
    {
        return $this->props($attributes);
    }

    /**
     * 追加字段组件根节点的 class。
     * 多次调用会自动合并，等价于设置组件的 `class` 属性。
     *
     * @param string|null $className class 名称；传 null 可移除。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->className('title-input')`
     */
    public function className(?string $className): static
    {
        return $this->prop('class', $className);
    }

    /**
     * 追加字段组件根节点的 style。
     * 多次调用会自动合并，等价于设置组件的 `style` 属性。
     *
     * @param string|null $style 内联样式；传 null 可移除。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->style('max-width:320px')`
     */
    public function style(?string $style): static
    {
        return $this->prop('style', $style);
    }

    /**
     * 为字段底层表单组件绑定事件。
     * 事件会直接绑定到最终渲染的 Element Plus / 自定义字段组件上，不作用于外层 el-form-item。
     * handler 使用组件原生事件参数，例如 change 通常写成 `(value) => {}`，blur 通常写成 `(event) => {}`。
     *
     * handler 函数体内可以直接读取这些辅助变量：
     * - `model`：当前字段所在的数据对象。普通表单里通常就是表单模型；
     *   在 Forms::object()、Forms::table() 行内会变成当前子对象或当前行对象。
     * - `form`：当前表单根模型，适合跨分组、跨行读取其他字段。
     * - `dialogRow`：字段渲染在弹窗 body 内时可用，表示来源表格行上下文；
     *   它不属于表单 `model`，不会随表单提交。
     *
     * 若字段内部也需要同名事件（例如 linkageUpdate 的 change），会自动与自定义事件串行执行。
     * 旧写法 `attr("@change", '...')` / `prop("@change", '...')` 仍会原样绑定到底层组件；
     * 新代码建议优先使用 on()，以获得 model/form 上下文和同名事件合并能力。
     *
     * @param string $event 事件名，可写 change / blur，也可写 @change / @update:model-value。
     * @param string|JsExpression $handler 前端事件处理函数或方法引用。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->on('change', "(value) => console.log(value, model.category_id, form.id)")`
     * - `Fields::radio('result', '确认结果')->on('change', "(value) => console.log(value, dialogRow?.id)")`
     */
    public function on(string $event, string|JsExpression $handler): static
    {
        $event = ltrim(trim($event), '@');
        if ($event === '') {
            return $this;
        }

        $this->eventHandlers[$event] ??= [];
        $this->eventHandlers[$event][] = $handler instanceof JsExpression
            ? $handler
            : JsExpression::make($handler);

        return $this;
    }

    /**
     * 批量绑定字段底层组件事件。
     * 事件参数、model/form 上下文和同名事件合并规则与 on() 一致。
     *
     * @param array<string, string|JsExpression|array<int, string|JsExpression>> $events
     * @return static 当前字段实例。
     */
    public function events(array $events): static
    {
        foreach ($events as $event => $handlers) {
            if (!is_string($event)) {
                continue;
            }

            $handlers = is_array($handlers) ? $handlers : [$handlers];
            foreach ($handlers as $handler) {
                if (is_string($handler) || $handler instanceof JsExpression) {
                    $this->on($event, $handler);
                }
            }
        }

        return $this;
    }

    /**
     * 为当前字段声明列表筛选搜索协议。
     * 主要用于 `ListWidget::filters()` 里的字段，把“筛选 UI + 搜索类型 + 真实字段映射”收敛到一处定义。
     * 未指定 type 时默认使用 `=`；未指定真实字段时默认使用当前字段 path/name。
     * 传 false 可显式关闭该字段参与搜索协议推导。
     *
     * @param string|bool $searchable 是否启用搜索或直接指定搜索操作符，默认值为 true。
     * @param string|null $field 后端真实字段名；传 null 时默认使用当前字段 path/name。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('user_name', '用户')->searchable('LIKE', 'user.name')`
     */
    public function searchable(
        #[ExpectedValues(Column::SUPPORTED_SEARCH_TYPES)]
        string|bool $searchable = true,
        ?string $field = null
    ): static {
        if ($searchable === false) {
            $this->searchEnabled = false;
            $this->searchConfig = [];

            return $this;
        }

        $this->searchEnabled = true;
        $this->searchConfig ??= [];
        $this->searchConfig['type'] = is_string($searchable) ? strtoupper($searchable) : '=';

        $normalizedField = is_string($field) ? trim($field) : '';
        if ($normalizedField !== '') {
            $this->searchConfig['field'] = $normalizedField;
        }

        return $this;
    }

    /**
     * 单独设置当前字段的搜索操作符。
     *
     * @param string $type 搜索操作符类型。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('keyword', '关键词')->searchType('LIKE')`
     */
    public function searchType(
        #[ExpectedValues(Column::SUPPORTED_SEARCH_TYPES)]
        string $type
    ): static {
        $this->searchEnabled = true;
        $this->searchConfig ??= [];
        $this->searchConfig['type'] = strtoupper($type);

        return $this;
    }

    /**
     * 单独设置当前字段映射到的真实搜索字段名。
     *
     * @param string $field 后端真实字段名。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('user_name', '用户')->searchField('user.name')`
     */
    public function searchField(string $field): static
    {
        $normalized = trim($field);
        if ($normalized === '') {
            return $this;
        }

        $this->searchEnabled = true;
        $this->searchConfig ??= [];
        $this->searchConfig['field'] = $normalized;

        return $this;
    }

    private function mergeFieldPropString(string $name, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return (string)($this->props[$name] ?? '');
        }

        $current = trim((string)($this->props[$name] ?? ''));
        if ($current === '') {
            return $value;
        }

        if ($name === 'style') {
            return rtrim($current, '; ') . '; ' . ltrim($value, '; ');
        }

        return trim($current . ' ' . $value);
    }

    /**
     * 设置字段占用的 24 栅格宽度，通常与 grid/section 搭配使用。
     *
     * @param int $span 栅格宽度，通常取 1-24。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->span(12)`
     */
    public function span(int $span): static
    {
        $this->span = max(1, min(24, $span));

        return $this;
    }

    /**
     * 设置字段下方的帮助说明文字。
     *
     * @param string $helpText 帮助说明。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('slug', '标识')->helpText('仅支持英文、数字和中划线')`
     */
    public function helpText(string $helpText): static
    {
        $this->helpText = $helpText;

        return $this;
    }

    /**
     * 直接禁用当前字段。
     *
     * @param bool $disabled 是否禁用，默认值为 true。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('code', '编码')->disabled()`
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * 设置字段只读。
     * 支持 readonly 的组件会输出 `readonly`；
     * 不支持 readonly 的组件会自动退化为 `disabled`。
     *
     * @param bool $readonly 是否只读，默认值为 true。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('code', '编码')->readonly()`
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * 在字段右侧追加操作按钮，适合“选择/查看/跳转”类辅助动作。
     * 动作 props 中的 v-if / v-show / :xxx 表达式可使用 `model` 指代当前表单模型；
     * 点击时如需读取/修改表单数据，请使用 Action::on('click', ...) 获取 action context。
     *
     * @param Action ...$actions 要追加的右侧动作。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('secret', '秘钥')->suffixActions(Actions::custom('生成')->props(['v-if' => '!model.id']))`
     */
    public function suffixActions(Action ...$actions): static
    {
        $this->suffixActions = array_merge($this->suffixActions, $actions);

        return $this;
    }

    /**
     * 在字段右侧追加说明内容，可传纯文本或轻量 HTML 元素。
     *
     * @param string|AbstractHtmlElement|null $content 右侧补充内容。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('code', '编码')->suffixContent('自动生成')`
     */
    public function suffixContent(string|AbstractHtmlElement|null $content): static
    {
        $this->suffixContent = $content;

        return $this;
    }

    /**
     * 控制当前字段是否在 PHP 层生效。
     *
     * 不可见时字段不会参与渲染、表单 schema、默认值、校验规则、选项/上传/弹窗等收集；
     * 适合权限、页面模式这类服务端已知条件。若需要根据表单 model 动态切换展示，请使用 visibleWhen()。
     *
     * @param bool $visible 是否显示并参与表单构建，默认值为 true。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('cost', '成本')->visible($canViewCost)`
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * 按条件控制字段显示，表达式上下文会自动注入当前字段相关的运行时信息。
     * 传入字符串时会按原样作为前端 JS 表达式注入，不会再包裹引号。
     * 在 object/arrayGroup/table 等子作用域中，`model` 会自动切到当前子模型。
     * 当前默认可用变量包括：
     * - `model`：当前字段所在的局部模型。
     *   顶层字段时通常就是整个表单；object 分组里是当前对象；arrayGroup/table 行内通常是当前行对象。
     * - `form`：当前表单根模型，适合跨分组、跨行读取其它字段。
     * - `state`：当前页面运行时 state。
     *   包含 `Pages::state()` 写入的数据，也包含 `Forms::state()` 写入的数据；
     *   表单级 state 通常挂在 `state.forms[scope]` 下。
     * - `pageState`：`state` 的语义化别名，当前实现里两者指向同一份对象。
     * - `scope`：当前表单 scope / key，例如 `article-form`、`dialog:detail`。
     *   若当前上下文没有显式 scope，可能为 `null`；它不是 Element Plus 表格插槽的 `scope`。
     * - `dialogRow`：当字段渲染在弹窗 body/footer 内时可用，表示打开弹窗的当前表格行数据。
     *   它不属于表单 `model`，不会随表单提交；适合只用于显示、禁用、只读、校验条件判断。
     * - `fieldName`：当前字段在表单中的完整路径，例如 `status`、`profile.dept_id`。
     *   数组行内会自动解析成当前行的运行时字段路径。
     * - `vm`：当前页面根 Vue 实例 / runtime 宿主对象。
     *   可用于调用公开 runtime 方法，但纯条件判断通常优先使用前面的结构化变量。
     * - `options`：当前字段已解析完成的选项数组。
     *   适用于 select/radio/checkbox/cascader 等选项字段；其它字段默认通常是空数组。
     * - `fieldConfig`：当前字段的运行时配置对象。
     *   目前主要用于选项类字段，可能包含远端选项配置、选项来源配置等；无配置时通常为 `{}`。
     * - `optionLoading`：当前字段选项是否正在加载。
     *   主要对远端或动态选项字段有意义，其它字段通常为 `false`。
     * - `optionLoaded`：当前字段选项是否至少完成过一次加载/写入。
     *   主要对远端或动态选项字段有意义，其它字段通常为 `false`。
     * - `field`：当前字段的静态元信息快照。
     *   当前至少包含 `name`、`path`、`label`、`type`、`visible`、`disabled`、`readonly`、`props`。
     * - `props`：`field.props` 的快捷别名，表示当前字段最终声明的组件属性。
     *   适合直接判断 `props.multiple`、`props.clearable` 这类配置。
     * 在 `Forms::table()` 的条件表达式里读取当前行时，优先使用 `model.xxx`；
     * `scope.row` / `scope.$index` 只适合表格单元格自定义模板内容本身。
     * 行操作弹窗表单里若只需要读取来源表格行而不提交该值，使用 `dialogRow.xxx`。
     * 例如：`model.type === "custom"`、`dialogRow?.business_type?.scene == 1`、`options.length > 0`。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('other', '其他')->visibleWhen('model.type === \"other\"')`
     */
    public function visibleWhen(string|JsExpression $expression): static
    {
        $this->visibleWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按条件控制字段禁用，表达式上下文与 visibleWhen() 一致。
     * 用法与 visibleWhen() 一致，只是最终作用到组件的 `disabled` 状态。
     * 详细变量说明见 visibleWhen() 注释。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->disabledWhen('model.locked === true')`
     */
    public function disabledWhen(string|JsExpression $expression): static
    {
        $this->disabledWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按条件控制字段只读，表达式上下文与 visibleWhen() 一致。
     * 支持 readonly 的组件会绑定 `readonly`；
     * 其余组件会自动退化为 `disabled`。
     * 详细变量说明见 visibleWhen() 注释。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->readonlyWhen('model.locked === true')`
     */
    public function readonlyWhen(string|JsExpression $expression): static
    {
        $this->readonlyWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 设置当前字段的 label 宽度，覆盖表单级别的 label-width。
     *
     * @param int|string $width label 宽度；数字会自动补上 px 单位，
     *                           字符串按原样处理（如 '120px'、'auto'）。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->labelWidth(120)`
     */
    public function labelWidth(int|string $width): static
    {
        if (is_int($width)) {
            $width = $width . 'px';
        } elseif (is_numeric($width) && trim($width) !== '') {
            $width = trim($width) . 'px';
        }

        $this->labelWidth = $width;

        return $this;
    }

    public function getLabelWidth(): ?string
    {
        return $this->labelWidth;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function hasLabel(): bool
    {
        return trim($this->label) !== '';
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function type(): FieldType
    {
        return $this->type;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function getEventHandlers(?string $event = null): array
    {
        if ($event === null) {
            return $this->eventHandlers;
        }

        $event = ltrim(trim($event), '@');

        return $this->eventHandlers[$event] ?? [];
    }

    public function hasEventHandlers(?string $event = null): bool
    {
        if ($event === null) {
            return $this->eventHandlers !== [];
        }

        return $this->getEventHandlers($event) !== [];
    }

    public function getSpan(): int
    {
        return $this->span;
    }

    public function getHelpText(): string
    {
        return $this->helpText;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getSuffixActions(): array
    {
        return array_values(array_filter(
            $this->suffixActions,
            static fn (Action $action): bool => $action->isAvailable()
        ));
    }

    public function getSuffixContent(): string|AbstractHtmlElement|null
    {
        return $this->suffixContent;
    }

    public function hasSuffix(): bool
    {
        return $this->getSuffixActions() !== []
            || ($this->suffixContent !== null && $this->suffixContent !== '');
    }

    public function getVisibleWhen(): ?JsExpression
    {
        return $this->visibleWhen;
    }

    public function getDisabledWhen(): ?JsExpression
    {
        return $this->disabledWhen;
    }

    public function getReadonlyWhen(): ?JsExpression
    {
        return $this->readonlyWhen;
    }

    public function hasSearchConfig(): bool
    {
        return $this->searchConfig !== null;
    }

    public function isSearchEnabled(): bool
    {
        return $this->searchEnabled;
    }

    public function getSearchConfig(): ?array
    {
        return $this->searchConfig;
    }
}
