<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
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
    private string $labelWidth = '100px';
    private string $submitLabel = '查询';
    private string $resetLabel = '重置';
    private ?string $loadUrl = null;
    private string $loadMethod = self::DEFAULT_LOAD_METHOD;
    private array|JsExpression $loadPayload = [];
    private bool $loadPayloadConfigured = false;
    private ?string $loadDataPath = null;
    private string $loadWhen = self::DEFAULT_LOAD_WHEN;
    private string $modeQueryKey = self::DEFAULT_MODE_QUERY_KEY;

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个表单组件实例。
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 追加字段叶子节点，适合简单表单直接堆字段。
     */
    public function addFields(Field ...$fields): self
    {
        return $this->addNodes(...$fields);
    }

    /**
     * 追加任意表单节点，支持字段、结构节点、作用域节点和数组节点混排。
     */
    public function addNodes(FormNode ...$nodes): self
    {
        $this->children = array_merge($this->children, $nodes);

        return $this;
    }

    /**
     * 切换整个表单为行内模式，常用于筛选表单。
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * 设置表单标签宽度，例如 96px / 120px。
     */
    public function labelWidth(string $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * 控制是否显示字段标签。
     * 适合紧凑型筛选条；关闭后会真正移除字段 label 输出，而不是只把宽度压成 0。
     */
    public function showLabels(bool $showLabels = true): self
    {
        $this->showLabels = $showLabels;

        return $this;
    }

    /**
     * 隐藏字段标签，常用于想在一行里放更多筛选项的表单。
     */
    public function hideLabels(bool $hideLabels = true): self
    {
        return $this->showLabels(!$hideLabels);
    }

    /**
     * 设置筛选模式下提交按钮文案。
     */
    public function submitLabel(string $submitLabel): self
    {
        $this->submitLabel = $submitLabel;

        return $this;
    }

    /**
     * 设置筛选模式下重置按钮文案。
     */
    public function resetLabel(string $resetLabel): self
    {
        $this->resetLabel = $resetLabel;

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
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     * - closeHostDialog() / reloadHostTable() / openHostDialog()
     *
     * 例如可写：
     * - `['id' => "@page.query.id"]`
     * - `"{ id: page.query.id, tab: query.tab }"`
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
     */
    public function loadDataPath(?string $loadDataPath): self
    {
        $this->loadDataPath = $loadDataPath;

        return $this;
    }

    /**
     * 控制独立表单页详情加载时机，仅支持 always / create / edit。
     * `edit` 为默认值，表示只有当前页面 query 中存在 modeQueryKey() 对应值时才自动拉取详情。
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
     * - `RequestAction::saveUrls()` 的新建/编辑地址自动切换
     * - 动作/事件上下文中的 `mode` / `page.mode`
     */
    public function modeQueryKey(string $queryKey = self::DEFAULT_MODE_QUERY_KEY): self
    {
        $queryKey = trim($queryKey);
        $this->modeQueryKey = $queryKey !== '' ? $queryKey : self::DEFAULT_MODE_QUERY_KEY;

        return $this;
    }

    /**
     * 绑定表单运行时事件。
     * 可用事件：validateSuccess / validateFail / arrayRowAdd / arrayRowRemove / arrayRowMove / optionsLoaded / optionsLoadFail / uploadSuccess / uploadFail。
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
     * - validateFail: error
     * - arrayRowAdd: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowRemove: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowMove: arrayPath / groupConfig / row / fromIndex / toIndex / direction / rows
     * - optionsLoaded: fieldName / fieldConfig / response / payload / options
     * - optionsLoadFail: fieldName / fieldConfig / error
     * - uploadSuccess: fieldName / fieldConfig / response / payload / uploadFile / uploadFiles
     * - uploadFail: fieldName / fieldConfig / error / response / uploadFile / uploadFiles
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

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getResetLabel(): string
    {
        return $this->resetLabel;
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

    public function defaults(): array
    {
        return $this->schema()->defaults();
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
}
