<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Dsl\Events;
use Sc\Util\HtmlStructureV2\Support\FormSchema;
use Sc\Util\HtmlStructureV2\Support\FormSchemaWalker;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Form implements Renderable, EventAware
{
    use HasEvents {
        on as private bindFormEventHandler;
    }
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = [
        'submitBefore',
        'submitSuccess',
        'submitFail',
        'submitFinally',
        'validateSuccess',
        'validateFail',
        'arrayRowAdd',
        'arrayRowRemove',
        'arrayRowMove',
        'optionsLoaded',
        'optionsLoadFail',
        'uploadSuccess',
        'uploadFail',
    ];
    private const DEFAULT_LOAD_METHOD = 'get';
    private const DEFAULT_LOAD_WHEN = 'edit';
    private const DEFAULT_MODE_QUERY_KEY = 'id';

    /** @var FormNode[] */
    private array $children = [];
    private bool $inline = false;
    private bool $showLabels = true;
    private bool $readonly = false;
    private string $labelWidth = '100px';
    private string $submitLabel = '查询';
    private string $resetLabel = '重置';
    /** @var Action[] */
    private array $footerActions = [];
    private ?string $loadUrl = null;
    private string $loadMethod = self::DEFAULT_LOAD_METHOD;
    private array|JsExpression $loadPayload = [];
    private bool $loadPayloadConfigured = false;
    private ?string $loadDataPath = null;
    private string $loadWhen = self::DEFAULT_LOAD_WHEN;
    private string $modeQueryKey = self::DEFAULT_MODE_QUERY_KEY;
    private string $saveMethod = 'post';
    private ?string $saveCreateUrl = null;
    private ?string $saveUpdateUrl = null;
    private ?string $saveLoadingText = null;
    private ?string $saveSuccessMessage = null;
    private ?string $saveErrorMessage = null;
    private ?array $initialData = null;

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个表单组件实例。
     *
     * @param string $key 表单唯一 key。
     * @return self 表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')`
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 追加字段叶子节点，适合简单表单直接堆字段。
     *
     * @param Field ...$fields 要追加的字段节点。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->addFields(Fields::text('title'), Fields::switch('status'))`
     */
    public function addFields(Field ...$fields): self
    {
        $this->children = array_merge($this->children, $fields);

        return $this;
    }

    /**
     * 追加任意表单节点，支持字段、结构节点、作用域节点和数组节点混排。
     *
     * @param FormNode ...$nodes 要追加的表单节点。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->addContent(Forms::section('基础信息')->addContent(...))`
     */
    public function addContent(FormNode ...$nodes): self
    {
        $this->children = array_merge($this->children, $nodes);

        return $this;
    }

    /**
     * 切换整个表单为行内模式，常用于筛选表单。
     *
     * @param bool $inline 是否启用行内模式，默认值为 true。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('filter-form')->inline()`
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * 设置表单标签宽度，例如 96px / 120px。
     *
     * @param string $labelWidth 标签宽度。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->labelWidth('120px')`
     */
    public function labelWidth(string $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * 控制是否显示字段标签。
     * 适合紧凑型筛选条；关闭后会真正移除字段 label 输出，而不是只把宽度压成 0。
     *
     * @param bool $showLabels 是否显示标签，默认值为 true。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('filter-form')->showLabels(false)`
     */
    public function showLabels(bool $showLabels = true): self
    {
        $this->showLabels = $showLabels;

        return $this;
    }

    /**
     * 把整个表单切为只读模式。
     * 文本类字段会尽量保持可选中状态，其余不支持 readonly 的组件会自动退化为 disabled。
     * 默认筛选按钮会自动隐藏；显式 footerActions() 不会被强制移除。
     *
     * @param bool $readonly 是否只读，默认值为 true。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->readonly()`
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * 设置筛选模式下提交按钮文案。
     *
     * @param string $submitLabel 提交按钮文案。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('filter-form')->submitLabel('搜索')`
     */
    public function submitLabel(string $submitLabel): self
    {
        $this->submitLabel = $submitLabel;

        return $this;
    }

    /**
     * 设置筛选模式下重置按钮文案。
     *
     * @param string $resetLabel 重置按钮文案。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('filter-form')->resetLabel('清空')`
     */
    public function resetLabel(string $resetLabel): self
    {
        $this->resetLabel = $resetLabel;

        return $this;
    }

    /**
     * 在普通表单底部追加动作按钮，适合常规 CRUD 页尾的“保存 / 重置 / 取消”。
     * 这些动作会在当前表单渲染作用域下运行；若动作依赖表单上下文，默认会优先命中当前表单。
     * 当前仅普通表单会渲染 footerActions()；筛选表单仍使用 submitLabel()/resetLabel() 那组内联按钮。
     *
     * @param Action ...$actions 要追加到表单底部的动作按钮。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->footerActions(Actions::save(), Actions::back('/admin/qa-info/lists'))`
     */
    public function footerActions(Action ...$actions): self
    {
        $this->footerActions = array_merge($this->footerActions, $actions);

        return $this;
    }

    /**
     * 配置独立表单页的详情加载接口。
     * 当前主要用于 `Page` 中直接放置的表单；弹窗表单仍优先使用 `Dialog::load()`。
     * 当 loadWhen() 条件命中时，会在页面初始化阶段请求该接口，再把结果回填到表单。
     * method 默认值为 get。
     *
     * 若未显式设置 loadPayload()，会默认按 modeQueryKey() 当前值自动提交同名查询参数，
     * 例如默认等价于 `{ id: 当前页面 query.id }`。
     *
     * @param string $url 详情加载接口地址。
     * @param string $method 请求方法，默认值为 get。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->load('/admin/qa-info/detail')`
     */
    public function load(string $url, string $method = 'get'): self
    {
        $this->loadUrl = $url;
        $this->loadMethod = strtolower($method) ?: self::DEFAULT_LOAD_METHOD;

        return $this;
    }

    /**
     * 设置独立表单页详情加载请求参数。
     * 数组/字符串/JsExpression 都会在请求前按页面运行时 context 解析。
     *
     * 常用可读取字段：
     * - query / page.query: 当前页面 URL 查询参数
     * - mode / page.mode: 当前页面模式，create 或 edit
     * - formScope / page.formScope: 当前表单 scope
     * - form / model / forms / dialogs / selection
     * - vm
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData() / setFormModel() / initializeFormModel() / resetForm()
     * - closeHostDialog() / reloadHostTable() / openHostDialog() / openHostTab()
     *
     * 例如可写：
     * - `['id' => "@page.query.id"]`
     * - `"{ id: page.query.id, tab: query.tab }"`
     *
     * @param array|string|JsExpression $loadPayload 加载接口请求体配置。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->loadPayload(['id' => '@page.query.id'])`
     */
    public function loadPayload(array|string|JsExpression $loadPayload): self
    {
        $this->loadPayload = $this->normalizeExpressionConfig($loadPayload);
        $this->loadPayloadConfigured = true;

        return $this;
    }

    /**
     * 设置从详情响应中取表单数据的路径。
     * 不设置时会自动尝试 `data` / `result` / `payload` 及响应对象本身里的对象结构。
     *
     * @param string|null $loadDataPath 响应数据路径，例如 data.info；传 null 表示使用自动推断。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->loadDataPath('data.info')`
     */
    public function loadDataPath(?string $loadDataPath): self
    {
        $this->loadDataPath = $loadDataPath;

        return $this;
    }

    /**
     * 控制独立表单页详情加载时机，仅支持 always / create / edit。
     * `edit` 为默认值，表示只有当前页面 query 中存在 modeQueryKey() 对应值时才自动拉取详情。
     *
     * @param string $loadWhen 加载时机，可选 always、create、edit。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->loadWhen('edit')`
     */
    public function loadWhen(string $loadWhen): self
    {
        $loadWhen = strtolower($loadWhen);
        if (in_array($loadWhen, ['always', 'create', 'edit'], true)) {
            $this->loadWhen = $loadWhen;
        }

        return $this;
    }

    /**
     * 设置独立表单页 create/edit 模式识别所用的查询参数名。
     * 默认值为 `id`；当当前页面 query 中该值非空时，页面模式会被视为 edit，否则为 create。
     *
     * 该配置会同时影响：
     * - load() 默认生成的详情加载参数
     * - `Form::saveUrls()` 与 `RequestAction::saveUrls()` 的新建/编辑地址自动切换
     * - 动作/事件上下文中的 `mode` / `page.mode`
     *
     * @param string $queryKey 用于识别 create/edit 模式的查询参数名，默认值为 id。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->modeQueryKey('info_id')`
     */
    public function modeQueryKey(string $queryKey = self::DEFAULT_MODE_QUERY_KEY): self
    {
        $queryKey = trim($queryKey);
        $this->modeQueryKey = $queryKey !== '' ? $queryKey : self::DEFAULT_MODE_QUERY_KEY;

        return $this;
    }

    /**
     * 配置表单保存地址；会按当前表单 modeQueryKey() 自动在 create/update 地址间切换。
     * 第二个参数为空时，新建和编辑都会复用同一个地址。
     *
     * @param string $createUrl 新建模式保存地址。
     * @param string|null $updateUrl 编辑模式保存地址；传 null 时回退到 $createUrl。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->saveUrls('/admin/qa-info/create', '/admin/qa-info/update')`
     */
    public function saveUrls(string $createUrl, ?string $updateUrl = null): self
    {
        $this->saveCreateUrl = $this->normalizeNullableString($createUrl);
        $this->saveUpdateUrl = $this->normalizeNullableString($updateUrl);
        if ($this->saveUpdateUrl === null) {
            $this->saveUpdateUrl = $this->saveCreateUrl;
        }

        return $this;
    }

    /**
     * 直接设置表单初始数据，适合像 V1 一样在 PHP 层先查好数据再回填。
     * 传入数据会按当前表单 schema 初始化，只保留已声明字段；
     * array group / 嵌套对象也会递归裁剪。
     *
     * @param array $data 初始数据；会按当前表单 schema 递归裁剪不存在的字段。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->setData(['title' => '示例', 'content' => '<p>内容</p>'])`
     */
    public function setData(array $data): self
    {
        $this->initialData = $data;

        return $this;
    }

    /**
     * 设置表单提交请求方法，默认值为 post。
     *
     * @param string $method 请求方法，默认值为 post。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->saveMethod('post')`
     */
    public function saveMethod(string $method = 'post'): self
    {
        $normalized = strtolower(trim($method));
        $this->saveMethod = $normalized !== '' ? $normalized : 'post';

        return $this;
    }

    /**
     * 设置表单提交中的 loading 文案；传 null 表示关闭 loading 文案。
     *
     * @param string|null $loadingText loading 文案；传 null 表示关闭文案。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->loadingText('正在保存，请稍后...')`
     */
    public function loadingText(?string $loadingText = '请稍后...'): self
    {
        $this->saveLoadingText = $this->normalizeNullableString($loadingText);

        return $this;
    }

    /**
     * 设置表单提交成功后的默认提示文案。
     *
     * @param string|null $successMessage 成功提示文案；传 null 表示不额外提示。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->successMessage('成功')`
     */
    public function successMessage(?string $successMessage): self
    {
        $this->saveSuccessMessage = $this->normalizeNullableString($successMessage);

        return $this;
    }

    /**
     * 设置表单提交失败后的默认提示文案。
     *
     * @param string|null $errorMessage 失败提示文案；传 null 表示沿用默认错误提示。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->errorMessage('保存失败，请重试')`
     */
    public function errorMessage(?string $errorMessage): self
    {
        $this->saveErrorMessage = $this->normalizeNullableString($errorMessage);

        return $this;
    }

    /**
     * 提交成功后执行“优先关闭宿主弹窗，否则跳转到指定 URL”的快捷方式。
     * 等价于给 submitSuccess 事件追加 `Events::returnTo($url)->hostTable($table)`。
     *
     * @param string|JsExpression|null $url 返回目标 URL；可为空，表示仅尝试关闭宿主弹窗。
     * @param string|Table|null $table 宿主表格 key 或 Table 对象；用于返回前刷新宿主表格。
     * @return self 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->returnTo('/admin/qa-info/lists', 'qa-info-table')`
     */
    public function returnTo(string|JsExpression|null $url = null, string|Table|null $table = null): self
    {
        return $this->on(
            'submitSuccess',
            Events::returnTo($url)->hostTable($table)
        );
    }

    /**
     * 绑定表单运行时事件。
     * 可用事件：
     * submitBefore / submitSuccess / submitFail / submitFinally /
     * validateSuccess / validateFail / arrayRowAdd / arrayRowRemove / arrayRowMove /
     * optionsLoaded / optionsLoadFail / uploadSuccess / uploadFail。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ scope, model, form, formConfig, error, fieldName, payload, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - scope: 当前表单作用域 key
     * - model / form: 当前表单数据模型
     * - formConfig: 当前表单运行时配置
     * - vm: 当前 Vue 实例
     *
     * 事件额外字段：
     * - submitBefore: request / payload
     * - submitSuccess: request / response / payload
     * - submitFail: request / error
     * - submitFinally: request，以及可能存在的 response / payload / error
     * - validateFail: error
     * - arrayRowAdd: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowRemove: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowMove: arrayPath / groupConfig / row / fromIndex / toIndex / direction / rows
     * - optionsLoaded: fieldName / fieldConfig / response / payload / options
     * - optionsLoadFail: fieldName / fieldConfig / error
     * - uploadSuccess: fieldName / fieldConfig / response / payload / uploadFile / uploadFiles
     * - uploadFail: fieldName / fieldConfig / error / response / uploadFile / uploadFiles
     *
     * @param string $event 事件名。
     * @param string|JsExpression|StructuredEventInterface $handler 事件处理逻辑。
     * @return static 当前表单实例。
     *
     * 示例：
     * `Form::make('qa-info-form')->on('submitSuccess', Events::returnTo('/admin/qa-info/lists'))`
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindFormEventHandler($event, $handler);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function schema(): FormSchema
    {
        return (new FormSchemaWalker())->build($this->children);
    }

    public function fields(): array
    {
        return array_map(
            static fn($fieldSchema) => $fieldSchema->field(),
            $this->schema()->fields()
        );
    }

    /**
     * @return FormNode[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getLabelWidth(): string
    {
        return $this->labelWidth;
    }

    public function shouldShowLabels(): bool
    {
        return $this->showLabels;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getResetLabel(): string
    {
        return $this->resetLabel;
    }

    /**
     * @return Action[]
     */
    public function getFooterActions(): array
    {
        return $this->footerActions;
    }

    public function getLoadUrl(): ?string
    {
        return $this->loadUrl;
    }

    public function getLoadMethod(): string
    {
        return $this->loadMethod;
    }

    public function getLoadPayload(): array|JsExpression
    {
        return $this->loadPayload;
    }

    public function shouldUseDefaultLoadPayload(): bool
    {
        return !$this->loadPayloadConfigured;
    }

    public function getLoadDataPath(): ?string
    {
        return $this->loadDataPath;
    }

    public function getLoadWhen(): string
    {
        return $this->loadWhen;
    }

    public function getModeQueryKey(): string
    {
        return $this->modeQueryKey;
    }

    public function getSaveMethod(): string
    {
        return $this->saveMethod;
    }

    public function getSaveCreateUrl(): ?string
    {
        return $this->saveCreateUrl;
    }

    public function getSaveUpdateUrl(): ?string
    {
        return $this->saveUpdateUrl;
    }

    public function getSaveLoadingText(): ?string
    {
        return $this->saveLoadingText;
    }

    public function getSaveSuccessMessage(): ?string
    {
        return $this->saveSuccessMessage;
    }

    public function getSaveErrorMessage(): ?string
    {
        return $this->saveErrorMessage;
    }

    public function defaults(): array
    {
        return $this->schema()->defaults();
    }

    public function initialData(): array
    {
        return $this->schema()->initializeData($this->initialData ?? []);
    }

    public function rules(): array
    {
        return $this->schema()->rules();
    }

    public function remoteOptions(): array
    {
        return $this->schema()->remoteOptions();
    }

    public function uploads(): array
    {
        return $this->schema()->uploads();
    }

    public function selectOptions(): array
    {
        return $this->schema()->selectOptions();
    }

    public function linkages(): array
    {
        return $this->schema()->linkages();
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'submitBefore' => '表单提交请求发出前触发，可读取 request / payload。',
            'submitSuccess' => '表单提交成功后触发，可读取 request / response / payload。',
            'submitFail' => '表单提交失败后触发，可读取 request / error。',
            'submitFinally' => '表单提交流程结束后触发，可读取 request，以及可能存在的 response / payload / error。',
            'validateSuccess' => '表单校验通过后触发。',
            'validateFail' => '表单校验失败后触发，可读取 error。',
            'arrayRowAdd' => '表单数组新增一行后触发，可读取 arrayPath / row / rowIndex / rows。',
            'arrayRowRemove' => '表单数组删除一行后触发，可读取 arrayPath / row / rowIndex / rows。',
            'arrayRowMove' => '表单数组调整顺序后触发，可读取 arrayPath / row / fromIndex / toIndex / direction。',
            'optionsLoaded' => '远程选项加载成功后触发，可读取 fieldName / options / payload。',
            'optionsLoadFail' => '远程选项加载失败后触发，可读取 fieldName / error。',
            'uploadSuccess' => '上传成功后触发，可读取 fieldName / uploadFile / uploadFiles / payload。',
            'uploadFail' => '上传失败后触发，可读取 fieldName / uploadFile / uploadFiles / error。',
        ];
    }

    private function normalizeExpressionConfig(array|string|JsExpression $value): array|JsExpression
    {
        return is_string($value) ? JsExpression::ensure($value) : $value;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
