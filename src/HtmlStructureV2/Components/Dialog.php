<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\Conditionable;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Dialog implements Renderable, EventAware
{
    use HasEvents {
        on as private bindDialogEventHandler;
    }
    use Conditionable;
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = [
        'beforeOpen',
        'afterOpen',
        'beforeClose',
        'afterClose',
        'submitSuccess',
        'submitFail',
    ];

    private const DEFAULT_WIDTH = '760px';
    private const DEFAULT_IFRAME_HEIGHT = '70vh';
    private const DEFAULT_COMPONENT_OPEN_METHOD = 'onShow';
    private const DEFAULT_LOAD_METHOD = 'get';
    private const DEFAULT_LOAD_WHEN = 'edit';

    private string $width = self::DEFAULT_WIDTH;
    private ?string $height = null;
    private bool $heightConfigured = false;
    private bool $draggable = false;
    private bool $fullscreen = false;
    private ?bool $alignCenter = null;
    private bool $closeOnClickModal = false;
    private bool $destroyOnClose = true;
    private ?string $saveUrl = null;
    private ?string $createUrl = null;
    private ?string $updateUrl = null;
    private ?string $titleTemplate = null;
    private array $footerActions = [];
    private ?Form $form = null;
    private string|AbstractHtmlElement|null $content = null;
    private ?string $componentName = null;
    private array|JsExpression $componentProps = [];
    private array $componentAttrs = [];
    private ?string $componentOpenMethod = self::DEFAULT_COMPONENT_OPEN_METHOD;
    private ?string $componentCloseMethod = null;
    private array|JsExpression|null $iframeQuery = null;
    private ?string $iframeUrl = null;
    private bool $iframeHostEnabled = false;
    private bool $iframeFullscreenToggle = false;
    private ?string $iframeSubmitHandler = 'VueApp.submit';
    private ?string $loadUrl = null;
    private string $loadMethod = self::DEFAULT_LOAD_METHOD;
    private array|JsExpression $loadPayload = [];
    private ?string $loadDataPath = null;
    private string $loadWhen = self::DEFAULT_LOAD_WHEN;
    private ?JsExpression $beforeOpenHook = null;
    private ?JsExpression $afterOpenHook = null;
    private ?JsExpression $beforeCloseHook = null;
    private ?JsExpression $afterCloseHook = null;
    private array|JsExpression $contextData = [];
    private array $props = [];

    public function __construct(
        private readonly string $key,
        private readonly string $title
    ) {
    }

    /**
     * 直接创建一个弹窗组件实例。
     */
    public static function make(string $key, string $title): self
    {
        return new self($key, $title);
    }

    /**
     * 设置弹窗宽度，例如 760px / 80vw。
     * 默认值为 760px。
     */
    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * 设置弹窗高度，例如 70vh；传 null 表示自动高度。
     * 未显式设置时，iframe 弹窗默认高度为 70vh，其它类型默认自动高度。
     */
    public function height(?string $height): self
    {
        $this->heightConfigured = true;
        $this->height = is_string($height) ? trim($height) : null;
        if ($this->height === '') {
            $this->height = null;
        }

        return $this;
    }

    /**
     * 控制弹窗是否可拖拽。
     * 默认值为 false。
     */
    public function draggable(bool $draggable = true): self
    {
        $this->draggable = $draggable;

        return $this;
    }

    /**
     * 控制弹窗是否全屏显示。
     * 默认值为 false。
     */
    public function fullscreen(bool $fullscreen = true): self
    {
        $this->fullscreen = $fullscreen;

        return $this;
    }

    /**
     * 控制弹窗内容是否垂直居中。
     * 对 iframe 页面弹窗，如果未显式设置，默认会按原版行为自动开启；
     * 其它类型默认关闭。
     */
    public function alignCenter(bool $alignCenter = true): self
    {
        $this->alignCenter = $alignCenter;

        return $this;
    }

    /**
     * 控制点击遮罩层时是否关闭弹窗。
     * 默认值为 false。
     */
    public function closeOnClickModal(bool $closeOnClickModal = true): self
    {
        $this->closeOnClickModal = $closeOnClickModal;

        return $this;
    }

    /**
     * 控制关闭后是否销毁内部内容。
     * 默认值为 true。
     */
    public function destroyOnClose(bool $destroyOnClose = true): self
    {
        $this->destroyOnClose = $destroyOnClose;

        return $this;
    }

    /**
     * 设置统一保存接口，适合新建/编辑共用提交地址。
     * URL 会在前端提交时按当前 dialog context 解析。
     * 未被 `Actions::submit()->saveUrl()/createUrl()/updateUrl()` 显式覆盖时使用。
     * 常用可用字段：
     * - mode / row / dialogKey / tableKey
     * - dialogContext / data: 由 context() 解析出的附加上下文
     * - forms / filters / selection
     * - dialog / dialogs / vm
     * 例如可写 "@row.id"、"@dialogContext.currentId"、"@filters.keyword"。
     */
    public function saveUrl(?string $saveUrl): self
    {
        $this->saveUrl = $saveUrl;

        return $this;
    }

    /**
     * 设置新建提交接口。
     * 仅在 create 模式下使用；若未设置会回退到 saveUrl()。
     * 未被 `Actions::submit()->createUrl()/saveUrl()` 显式覆盖时使用。
     * token 解析字段与 saveUrl() 一致。
     */
    public function createUrl(?string $createUrl): self
    {
        $this->createUrl = $createUrl;

        return $this;
    }

    /**
     * 设置编辑提交接口。
     * 仅在 edit 模式下使用；若未设置会回退到 saveUrl()。
     * 未被 `Actions::submit()->updateUrl()/saveUrl()` 显式覆盖时使用。
     * token 解析字段与 saveUrl() 一致。
     */
    public function updateUrl(?string $updateUrl): self
    {
        $this->updateUrl = $updateUrl;

        return $this;
    }

    /**
     * 设置动态标题模板，例如 编辑 {name}。
     * `{field}` 会先从当前 `row` 中取值；若模板里还包含 token，也会在前端打开弹窗时继续解析。
     * 常用可用字段：
     * - row / mode / dialogKey / tableKey
     * - dialogContext / data
     * - forms / filters / selection
     * 例如可写 `编辑 {name}`、`预览 "@dialogContext.sourceLabel"`、`"@filters.keyword" 结果`。
     */
    public function titleTemplate(?string $titleTemplate): self
    {
        $this->titleTemplate = $titleTemplate;

        return $this;
    }

    /**
     * 绑定弹窗生命周期事件。
     * 可用事件：beforeOpen / afterOpen / beforeClose / afterClose / submitSuccess / submitFail。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ dialogKey, mode, row, tableKey, response, payload, error, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - dialogKey / mode / row / tableKey
     * - dialogConfig / dialog / dialogs
     * - forms / filters / selection
     * - dialogLoading / dialogSubmitting / dialogVisible
     * - dialogTitle / dialogFullscreen / dialogComponentProps
     * - dialogComponentRef / dialogIframeRef
     * - dialogContext / data: dialog->context(...) 解析后的附加上下文
     * - vm
     * - reloadTable() / closeDialog() / openDialog()
     * - setDialogTitle() / setDialogFullscreen() / toggleDialogFullscreen() / refreshDialogIframe()
     *
     * 事件额外字段：
     * - submitSuccess: response / payload / submitData
     * - submitFail: error / submitData
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindDialogEventHandler($event, $handler);
    }

    /**
     * 把弹窗主体切换为表单。
     */
    public function form(Form $form): self
    {
        $this->form = $form;
        $this->content = null;
        $this->componentName = null;
        $this->componentProps = [];
        $this->componentAttrs = [];
        $this->componentOpenMethod = self::DEFAULT_COMPONENT_OPEN_METHOD;
        $this->componentCloseMethod = null;
        $this->iframeUrl = null;
        $this->iframeQuery = null;
        $this->iframeSubmitHandler = null;

        return $this;
    }

    /**
     * 把弹窗主体切换为纯内容块。
     */
    public function content(string|AbstractHtmlElement|null $content): self
    {
        $this->content = $content;
        $this->form = null;
        $this->componentName = null;
        $this->componentProps = [];
        $this->componentAttrs = [];
        $this->componentOpenMethod = self::DEFAULT_COMPONENT_OPEN_METHOD;
        $this->componentCloseMethod = null;
        $this->iframeUrl = null;
        $this->iframeQuery = null;
        $this->iframeSubmitHandler = null;

        return $this;
    }

    /**
     * 把弹窗主体切换为 Vue 组件。
     * `name` 应是前端已注册的组件名。`props` 若传数组/字符串/JsExpression，会在每次打开弹窗时
     * 按当前 dialog context 解析；组件打开/关闭生命周期可再配合 componentOpenMethod()/componentCloseMethod()。
     *
     * props 解析时可用字段：
     * - row / mode / dialogKey / tableKey
     * - dialogContext / data / forms / filters / selection
     * - dialog / dialogs / dialogTitle / dialogFullscreen / dialogComponentProps
     * - dialogComponentRef / dialogIframeRef / vm
     * - reloadTable() / closeDialog() / openDialog()
     * - setDialogTitle() / setDialogFullscreen() / toggleDialogFullscreen() / refreshDialogIframe()
     */
    public function component(
        string $name,
        array|string|JsExpression $props = [],
        array $attrs = []
    ): self {
        $this->componentName = $name;
        $this->componentProps = $this->normalizeExpressionConfig($props);
        $this->componentAttrs = array_merge($this->componentAttrs, $attrs);
        $this->form = null;
        $this->content = null;
        $this->iframeUrl = null;
        $this->iframeQuery = null;
        $this->iframeSubmitHandler = null;

        return $this;
    }

    /**
     * 设置组件弹窗的 props。
     * 会在弹窗打开时按当前 dialog context 重新解析，适合把 `row`、`dialogContext`
     * 或其它运行时数据传给组件。传字符串时会自动包装成 JsExpression。
     * 可用字段与 component() 的 props 解析上下文一致。
     */
    public function componentProps(array|string|JsExpression $props): self
    {
        $this->componentProps = $this->normalizeExpressionConfig($props);

        return $this;
    }

    /**
     * 设置组件弹窗的 attrs。
     * 这里更适合放静态 attrs；若需要动态值，优先放到 componentProps() 里处理。
     */
    public function componentAttrs(array $attrs): self
    {
        $this->componentAttrs = array_merge($this->componentAttrs, $attrs);

        return $this;
    }

    /**
     * 设置组件弹窗打开时调用的实例方法。
     * 该方法会在组件挂载完成后被调用，并接收一个 dialog context 对象作为唯一参数。
     * 常用字段与 beforeOpen/afterOpen hook 一致，例如 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm。
     * 默认值为 onShow。
     */
    public function componentOpenMethod(?string $method): self
    {
        $this->componentOpenMethod = $method;

        return $this;
    }

    /**
     * 设置组件弹窗关闭时调用的实例方法。
     * 关闭完成后会调用组件实例上的同名方法，并传入关闭时的 dialog context。
     * 常用字段与 beforeClose/afterClose hook 一致，例如 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm。
     */
    public function componentCloseMethod(?string $method): self
    {
        $this->componentCloseMethod = $method;

        return $this;
    }

    /**
     * 把弹窗主体切换为 iframe 页面。
     * `query` 会在每次打开弹窗时按当前 dialog context 解析；
     * 传字符串时会自动包装成 JsExpression；
     * 默认同时开启宿主桥接和头部全屏切换；
     * 若底部动作使用 `Actions::submit()` 且配置了 saveUrl()/createUrl()/updateUrl()，
     * runtime 默认会先调用子页面的 `"VueApp.submit"` 取提交数据；
     * 可再用 iframeSubmitHandler() 改成别的方法路径；
     * 若未显式设置 height()，默认高度为 70vh。
     * 可用字段与 component() 的 props 解析上下文一致。
     */
    public function iframe(string $url, array|string|JsExpression $query = []): self
    {
        $this->iframeUrl = $url;
        $this->iframeQuery = $this->normalizeExpressionConfig($query);
        $this->iframeHostEnabled = true;
        $this->iframeFullscreenToggle = true;
        $this->form = null;
        $this->content = null;
        $this->componentName = null;
        $this->componentProps = [];
        $this->componentAttrs = [];
        $this->componentOpenMethod = self::DEFAULT_COMPONENT_OPEN_METHOD;
        $this->componentCloseMethod = null;
        $this->iframeSubmitHandler = 'VueApp.submit';

        return $this;
    }

    /**
     * 控制 iframe 弹窗是否启用宿主桥接。
     * 开启后，iframe 子页面可通过宿主桥接请求关闭弹窗、刷新表格、再次打开弹窗、改标题等。
     * iframe() 默认值为 true。
     */
    public function iframeHost(bool $enabled = true): self
    {
        $this->iframeHostEnabled = $enabled;

        return $this;
    }

    /**
     * 控制 iframe 弹窗是否显示头部全屏切换。
     * iframe() 默认值为 true。
     */
    public function iframeFullscreenToggle(bool $enabled = true): self
    {
        $this->iframeFullscreenToggle = $enabled;

        return $this;
    }

    /**
     * 设置 iframe 弹窗点击 `Actions::submit()` 时在子页面调用的提交方法路径。
     * 路径从 `iframe.contentWindow` 开始解析，默认值为 `"VueApp.submit"`。
     * 例如可写 `"VueApp.submit"`、`"submit"`、`"pageApi.submitForm"`。
     * 该方法应返回最终提交到 saveUrl()/createUrl()/updateUrl() 的数据，也可以直接返回 Promise。
     * 该能力依赖宿主页直接访问 iframe 子页面对象，通常要求子页面与宿主页同源。
     */
    public function iframeSubmitHandler(?string $handlerPath = 'VueApp.submit'): self
    {
        $handlerPath = is_string($handlerPath) ? trim($handlerPath) : null;
        $this->iframeSubmitHandler = $handlerPath !== '' ? $handlerPath : null;

        return $this;
    }

    /**
     * 配置弹窗打开时的详情加载接口。
     * 当 loadWhen() 条件命中时，会在弹窗打开流程中请求该接口，再把结果回填到表单。
     * `url` 同样支持运行时 token。
     * method 默认值为 get。
     * 常用可用字段：
     * - row / mode / dialogKey / tableKey
     * - dialogContext / data / forms / filters / selection
     * - dialog / dialogs / vm
     * 例如可写 "@row.id"、"@dialogContext.currentId"、"@filters.keyword"。
     */
    public function load(string $url, string $method = 'get'): self
    {
        $this->loadUrl = $url;
        $this->loadMethod = strtolower($method) ?: 'get';

        return $this;
    }

    /**
     * 设置详情加载请求参数。
     * 数组/字符串/JsExpression 都会在请求前按当前 dialog context 解析，
     * 常用可读取字段：
     * - row / mode / dialogKey / tableKey
     * - dialogContext / data / forms / filters / selection
     * - dialog / dialogs / vm
     * - reloadTable() / closeDialog() / openDialog()
     * - setDialogTitle() / setDialogFullscreen() / toggleDialogFullscreen() / refreshDialogIframe()
     */
    public function loadPayload(array|string|JsExpression $loadPayload): self
    {
        $this->loadPayload = $this->normalizeExpressionConfig($loadPayload);

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
     * 控制详情加载时机，仅支持 always / create / edit。
     * `edit` 为默认值，表示只有带 row 打开时才自动拉取详情。
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
     * 设置弹窗打开前钩子。
     * handler 签名与 `on('beforeOpen', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 常用字段与 beforeOpen 事件一致，可读取 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm 及弹窗辅助方法。
     */
    public function beforeOpen(string|JsExpression $beforeOpenHook): self
    {
        $beforeOpenHook = JsExpression::ensure($beforeOpenHook);
        $this->beforeOpenHook = $beforeOpenHook;
        $this->on('beforeOpen', $beforeOpenHook);

        return $this;
    }

    /**
     * 设置弹窗打开后钩子。
     * handler 签名与 `on('afterOpen', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 常用字段与 afterOpen 事件一致，可读取 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm 及弹窗辅助方法。
     */
    public function afterOpen(string|JsExpression $afterOpenHook): self
    {
        $afterOpenHook = JsExpression::ensure($afterOpenHook);
        $this->afterOpenHook = $afterOpenHook;
        $this->on('afterOpen', $afterOpenHook);

        return $this;
    }

    /**
     * 设置弹窗关闭前钩子。
     * handler 签名与 `on('beforeClose', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 常用字段与 beforeClose 事件一致，可读取 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm 及弹窗辅助方法。
     */
    public function beforeClose(string|JsExpression $beforeCloseHook): self
    {
        $beforeCloseHook = JsExpression::ensure($beforeCloseHook);
        $this->beforeCloseHook = $beforeCloseHook;
        $this->on('beforeClose', $beforeCloseHook);

        return $this;
    }

    /**
     * 设置弹窗关闭后钩子。
     * handler 签名与 `on('afterClose', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 常用字段与 afterClose 事件一致，可读取 row / mode / dialogKey / tableKey /
     * dialogContext / data / forms / filters / selection / dialog / dialogs / vm 及弹窗辅助方法。
     */
    public function afterClose(string|JsExpression $afterCloseHook): self
    {
        $afterCloseHook = JsExpression::ensure($afterCloseHook);
        $this->afterCloseHook = $afterCloseHook;
        $this->on('afterClose', $afterCloseHook);

        return $this;
    }

    /**
     * 注入弹窗上下文数据，供标题模板、请求参数或组件消费。
     * 每次打开弹窗时都会按当前基础 context 解析，并最终合并到 `dialogContext` / `data`。
     * 适合集中准备 `titleTemplate()`、`loadPayload()`、`componentProps()`、`iframe()` 要复用的上下文。
     * 传字符串时会自动包装成 JsExpression。
     *
     * 当前这一步可用的基础字段：
     * - row / mode / dialogKey / tableKey
     * - forms / filters / selection
     * - dialog / dialogs / vm
     * - reloadTable() / closeDialog() / openDialog()
     * - setDialogTitle() / setDialogFullscreen() / toggleDialogFullscreen() / refreshDialogIframe()
     *
     * 注意：这里解析的是“生成 dialogContext 之前”的基础上下文，
     * 因此不能依赖本次 context() 刚准备生成的 `dialogContext` / `data` 自身。
     */
    public function context(array|string|JsExpression $contextData): self
    {
        $contextData = $this->normalizeExpressionConfig($contextData);

        if (is_array($contextData) && is_array($this->contextData)) {
            $this->contextData = array_merge($this->contextData, $contextData);
        } else {
            $this->contextData = $contextData;
        }

        return $this;
    }

    /**
     * 透传额外弹窗属性。
     */
    public function props(array $props): self
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    /**
     * 设置弹窗底部动作按钮。
     * 这些动作运行时同样可获得当前 dialog context，因此可直接配合 close/reload/request 使用。
     */
    public function footer(Action ...$actions): self
    {
        $this->footerActions = array_merge($this->footerActions, $actions);

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'beforeOpen' => '弹窗打开前触发，返回 false 可取消打开。',
            'afterOpen' => '弹窗完成打开后触发，适合补充联动或通知子组件。',
            'beforeClose' => '弹窗关闭前触发，返回 false 可取消关闭。',
            'afterClose' => '弹窗关闭完成后触发，适合清理上下文。',
            'submitSuccess' => '表单弹窗或 iframe 弹窗提交成功后触发，可读取 response / payload / dialog / submitData。',
            'submitFail' => '表单弹窗或 iframe 弹窗提交失败后触发，可读取 error / dialog / submitData。',
        ];
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getHeight(): ?string
    {
        if ($this->heightConfigured) {
            return $this->height;
        }

        if ($this->bodyType() === 'iframe') {
            return self::DEFAULT_IFRAME_HEIGHT;
        }

        return null;
    }

    public function isDraggable(): bool
    {
        return $this->draggable;
    }

    public function isFullscreen(): bool
    {
        return $this->fullscreen;
    }

    public function isAlignCenter(): bool
    {
        if ($this->alignCenter !== null) {
            return $this->alignCenter;
        }

        return $this->bodyType() === 'iframe';
    }

    public function shouldCloseOnClickModal(): bool
    {
        return $this->closeOnClickModal;
    }

    public function shouldDestroyOnClose(): bool
    {
        return $this->destroyOnClose;
    }

    public function getSaveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function getCreateUrl(): ?string
    {
        return $this->createUrl;
    }

    public function getUpdateUrl(): ?string
    {
        return $this->updateUrl;
    }

    public function getTitleTemplate(): ?string
    {
        return $this->titleTemplate;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function getContent(): string|AbstractHtmlElement|null
    {
        return $this->content;
    }

    public function getComponentName(): ?string
    {
        return $this->componentName;
    }

    public function getComponentProps(): array|JsExpression
    {
        return $this->componentProps;
    }

    public function getComponentAttrs(): array
    {
        return $this->componentAttrs;
    }

    public function getComponentOpenMethod(): ?string
    {
        return $this->componentOpenMethod;
    }

    public function getComponentCloseMethod(): ?string
    {
        return $this->componentCloseMethod;
    }

    public function getIframeUrl(): ?string
    {
        return $this->iframeUrl;
    }

    public function getIframeQuery(): array|JsExpression|null
    {
        return $this->iframeQuery;
    }

    public function isIframeHostEnabled(): bool
    {
        return $this->iframeHostEnabled;
    }

    public function hasIframeFullscreenToggle(): bool
    {
        return $this->iframeFullscreenToggle;
    }

    public function getIframeSubmitHandler(): ?string
    {
        return $this->iframeSubmitHandler;
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

    public function getLoadDataPath(): ?string
    {
        return $this->loadDataPath;
    }

    public function getLoadWhen(): string
    {
        return $this->loadWhen;
    }

    public function getBeforeOpenHook(): ?JsExpression
    {
        return $this->beforeOpenHook;
    }

    public function getAfterOpenHook(): ?JsExpression
    {
        return $this->afterOpenHook;
    }

    public function getBeforeCloseHook(): ?JsExpression
    {
        return $this->beforeCloseHook;
    }

    public function getAfterCloseHook(): ?JsExpression
    {
        return $this->afterCloseHook;
    }

    public function getContextData(): array|JsExpression
    {
        return $this->contextData;
    }

    private function normalizeExpressionConfig(array|string|JsExpression $value): array|JsExpression
    {
        return is_string($value) ? JsExpression::ensure($value) : $value;
    }

    public function getFooterActions(): array
    {
        return $this->footerActions;
    }

    public function attrs(): array
    {
        return $this->props;
    }

    public function bodyType(): string
    {
        if ($this->iframeUrl !== null && $this->iframeUrl !== '') {
            return 'iframe';
        }

        if ($this->componentName !== null && $this->componentName !== '') {
            return 'component';
        }

        if ($this->form !== null) {
            return 'form';
        }

        if ($this->content !== null) {
            return 'content';
        }

        return 'empty';
    }
}
