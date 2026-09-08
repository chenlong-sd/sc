<?php

namespace Sc\Util\HtmlStructureV2\Page;

use InvalidArgumentException;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Contracts\DocumentRenderable;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\Conditionable;
use Sc\Util\HtmlStructureV2\Support\Document;
use Sc\Util\HtmlStructureV2\Support\FormPath;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdminTheme;

abstract class AbstractPage implements DocumentRenderable, Renderable
{
    use Conditionable;
    use RendersWithTheme;

    private const BACKGROUND_PRESETS = [
        'white' => '#ffffff',
        'muted' => '#f5f7fa',
        'transparent' => 'transparent',
    ];

    /**
     * 禁止注册为页面方法的方法名（与运行时内建实例方法同名）。
     *
     * 页面方法会被运行时绑定到 Vue 实例以支持模板裸调 openMap(...)；与内建方法同名会
     * 破坏页面协议，在此构建期直接抛错。运行时绑定层另有"内建优先 + 告警"兜底，
     * 此处为作者体验更早的强校验。
     */
    private const RESERVED_METHOD_NAMES = [
        'callPageMethod', 'getPageMethod', 'callFormMethod', 'getFormMethod',
        'getState', 'setState', 'getFormState', 'setFormState', 'getPageQuery',
        'resolvePageMode', 'resolveFormMode', 'resolveNamedEventHandler', 'isFormReadonly',
        'validateForm', 'validateSimpleForm', 'clearFormValidate', 'clearSimpleFormValidate',
        'resetForm', 'getFormModel', 'cloneFormModel', 'setFormModel',
        'initializeFormModel', 'loadFormData',
        'openDialog', 'closeDialog', 'notifyDialogHost', 'closeHostDialog',
        'reloadHostTable', 'openHostDialog', 'openHostUrlDialog', 'openHostTab',
        'setHostDialogTitle', 'setHostDialogFullscreen', 'toggleHostDialogFullscreen',
        'refreshHostDialogIframe', 'syncHostDialogSubmitState',
    ];

    private array $headerActions = [];
    private array $headerContent = [];
    private array $sections = [];
    private array $dialogs = [];
    private array $state = [];
    /** @var array<string, JsExpression> */
    private array $methods = [];
    /** @var string[] 运行时之后注入的 <script src> 外部脚本（不阻塞页面启动） */
    private array $externalScripts = [];
    /** @var string[] 运行时之前注入的 <script src> 外部脚本（阻塞启动，挂载前必须可用） */
    private array $externalScriptsBeforeRuntime = [];
    /** @var string[] 页面加载时注入的 <link rel="stylesheet"> 外部样式 */
    private array $externalStyles = [];
    private ?string $background = null;
    private ?ThemeInterface $renderTheme = null;

    public function __construct(
        private string $title,
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个页面实例。
     * "$title" 用于 HTML "<title>"；页面头部展示建议通过 "->header(...)" 自定义。
     *
     * @param string $title 页面 HTML 标题。
     * @param string|null $key 页面唯一 key；传 null 时自动由标题推导。
     * @return static 页面实例。
     *
     * 示例：
     * - `Page::make('问答信息')->header('问答信息管理')`
     */
    public static function make(string $title, ?string $key = null): static
    {
        return new static($title, $key ?: static::normalizeKey($title));
    }

    /**
     * 设置 HTML "<title>"。
     * 只影响浏览器标签标题和文档标题，不直接决定页面头部怎么展示。
     *
     * @param string $title HTML 标题。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->htmlTitle('问答信息管理')`
     */
    public function htmlTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置页面头部展示内容。
     * 支持轻组件树、原始字符串或 "AbstractHtmlElement"，推荐组合 "Blocks::title()"、"Blocks::text()"、"Layouts::stack()"。
     * 页面标题、说明文案等可见头部内容都应通过这里显式组合。
     *
     * @param string|AbstractHtmlElement|Renderable ...$content 页面头部内容。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->header('问答信息管理', '支持创建和编辑问答')`
     */
    public function header(string|AbstractHtmlElement|Renderable ...$content): static
    {
        $this->headerContent = $content;

        return $this;
    }

    /**
     * 设置页面根容器背景。
     * 支持任意 CSS "background" 值，例如颜色、渐变或图片。
     *
     * @param string $background CSS background 值。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->background('#f5f7fa')`
     */
    public function background(string $background): static
    {
        $background = trim($background);
        $this->background = $background !== '' ? $background : self::BACKGROUND_PRESETS['white'];

        return $this;
    }

    /**
     * 设置页面背景预设。
     * 当前支持："white"、"muted"、"transparent"。
     *
     * @param string $preset 背景预设名。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->backgroundPreset('muted')`
     */
    public function backgroundPreset(string $preset): static
    {
        if (!array_key_exists($preset, self::BACKGROUND_PRESETS)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported V2 page background preset [%s], supported presets: %s',
                $preset,
                implode(', ', array_keys(self::BACKGROUND_PRESETS))
            ));
        }

        $this->background = self::BACKGROUND_PRESETS[$preset];

        return $this;
    }

    /**
     * 设置页面默认渲染主题，显式传给 toHtml($theme) 时以 toHtml 参数为准。
     *
     * @param ThemeInterface $theme 渲染主题实例。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->theme(new ElementPlusAdminTheme())`
     */
    public function theme(ThemeInterface $theme): static
    {
        $this->renderTheme = $theme;

        return $this;
    }

    /**
     * 设置页面头部动作按钮。
     *
     * @param Action ...$actions 页面头部动作。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->actions(Actions::create()->dialog('qa-info-dialog'))`
     */
    public function actions(Action ...$actions): static
    {
        $this->headerActions = array_merge($this->headerActions, $actions);

        return $this;
    }

    /**
     * 向页面主体追加一个或多个区块。
     *
     * @param Renderable ...$sections 页面主体区块。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->addSection($list, $dialog)`
     */
    public function addSection(Renderable ...$sections): static
    {
        $this->sections = array_merge($this->sections, $sections);

        return $this;
    }

    /**
     * 设置页面级前端运行时 state。
     *
     * 数据挂在 Vue 的 pageState 上，并非顶层变量：
     * - 模板里以 `pageState.<path>` 读取，如 `{{ pageState.map.address }}`；写成 `{{ map.address }}` 解析不到；
     * - 运行时读写一律用 getState()/setState()——实例方法 `vm.getState(path, fallback)` /
     *   `vm.setState(path, value)`，或全局 `__SC_V2_PAGE__.getState()/setState()`；
     *   页面方法（method()）内可直接用 ctx 门面：`ctx.setState(path, value)` / `ctx.getState(path)`（见 method() 说明）；
     * - 表单 options 可通过 optionsState()/computedOptions() 引用。
     *
     * @param string $path state 路径，支持点号嵌套，如 "map.address"。
     * @param mixed $value state 值。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('文章')->state('statusOptions', [...])`
     * - `->state('map', ['address' => '', 'lang' => ''])`，模板引用 `{{ pageState.map.address }}`；
     *   页面方法内 `ctx.setState('map', ['address' => ..., 'lang' => ...])` 更新，未注册路径 dev 下会 console.warn。
     */
    public function state(string $path, mixed $value): static
    {
        $path = FormPath::normalize($path);
        if ($path === '') {
            return $this;
        }

        FormPath::set($this->state, $path, $value);

        return $this;
    }

    /**
     * 批量合并页面级前端运行时 state。
     * 用法同 state()：数据挂在 `pageState` 下，模板引用 `pageState.<path>`，
     * 运行时用 `ctx.setState()/getState()` 或 `__SC_V2_PAGE__.setState()/getState()` 读写。
     *
     * @param array $state state 数据。
     * @return static 当前页面实例。
     */
    public function states(array $state): static
    {
        $this->state = array_replace_recursive($this->state, $state);

        return $this;
    }

    /**
     * 注册当前页面可复用的前端方法。
     *
     * 页面方法只存于运行时配置，不会注入为 Vue 组件实例方法，必须通过 `callPageMethod()` 调用：
     * - 模板事件绑定：`'@click' => "callPageMethod('openMap', 1231)"`（渲染为 `@click="callPageMethod('openMap', 1231)"`）
     * - 组件动作回调：`'({ row, vm }) => vm.callPageMethod("openMap", { row })'`
     * - 页面方法内部再调用另一方法：`ctx.callPageMethod("openMapRefresh", ...)`
     *
     * 模板事件里直接写方法名（如 `'@click' => "openMap(1231)"`）会被 Vue 编译为 `_ctx.openMap(1231)`，
     * 实例上不存在 openMap 属性，点击即抛 `openMap is not a function`——这是最常见的误用。
     *
     * 事件绑定必须用 `@click`（v-on）；`':click'`（v-bind）是属性绑定，表达式会在**每次渲染时求值**——
     * 若方法体里有 `ctx.setState()` / `ctx.openDialog()` 等响应式变更，会触发重渲染再求值，
     * 页面直接卡死（页面打开即默认渲染触发）。误用 `:click` 的页面方法请一律改成 `'@click'`。
     *
     * 页面动作、表单事件或字段事件在找不到同名表单方法时，会继续回退到页面方法（dev 下 console.warn 提醒）。
     *
     * 方法体（`(ctx) => {...}` 箭头函数）的 `this` 在页面加载时按外层作用域固定为 window，
     * 箭头函数无法被 .call()/bind() 重绑——因此方法内不要写 `this.xxx`，也无需先取 vm，
     * 运行时已为 ctx 挂好门面方法（均绑定到页面 Vue 实例，且不可被 ctx 覆盖）：
     * `ctx.setState(path, value)`、`ctx.getState(path, fallback)`、`ctx.callPageMethod(name, ctx)`、
     * `ctx.callFormMethod(scope, name, ctx)`、`ctx.getFormMethod(scope, name)`、
     * `ctx.openDialog('key')`（仅 simple 页面运行时存在；list 页面无该能力时 ctx.openDialog 保持未定义）。
     * `ctx.vm` 仍保留，用作兜底（`ctx.vm` 乃至 `window.__SC_V2_PAGE__?.vm`）。
     *
     * 命名方法统一只接收一个 `ctx` 对象，常见字段会按触发来源自动注入，例如：
     * - `ctx.vm`: 当前页面 Vue 实例
     * - `ctx.scope` / `ctx.formScope`: 当前表单 scope（若事件来自某个表单）
     * - `ctx.model` / `ctx.form`: 当前表单模型
     * - `ctx.value` / `ctx.event` / `ctx.args`: 字段原生事件参数（callPageMethod 传普通值时注入；传对象则并入 ctx）
     * - `ctx.row` / `ctx.tableKey` / `ctx.listKey` / `ctx.selection`: 动作或列表上下文
     *
     * 调用时传普通值，ctx 形如 `{ vm, methodName, value, event, args }`；
     * 传对象时会并入默认值：`callPageMethod('openMap', { row })` 中 `ctx.row` 即为该 row。
     *
     * 示例：
     * - `->method('openMap', <<<'JS' (ctx) => { ctx.setState('map', {address: ctx.value}); ctx.openDialog('mapd'); } JS)`
     * - 模板事件：`'@click' => "callPageMethod('openMap', 1231)"`，方法内 `ctx.value` 即为 1231
     * - 模板也可直接裸调：`'@click' => "openMap(1231)"`——运行时把页面方法绑定到 Vue 实例，
     *   与 callPageMethod 走同一套 ctx 构造。方法名需为合法 JS 标识符，且不得与内建方法同名
     *   （同名在注册期抛 InvalidArgumentException）。
     *
     * @param string $name 方法名。
     * @param string|JsExpression $handler 前端函数表达式，推荐写成 `(ctx) => { ... }`。
     * @return static 当前页面实例。
     * @throws InvalidArgumentException 方法名与运行时内建方法同名。
     */
    public function method(string $name, string|JsExpression $handler): static
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }

        if (in_array($name, self::RESERVED_METHOD_NAMES, true)) {
            throw new InvalidArgumentException(sprintf(
                '页面方法名 "%s" 与运行时内建方法同名，禁止注册；请换一个名字，或继续通过 callPageMethod("%s", ...) 调用协议方法。',
                $name, $name
            ));
        }

        $this->methods[$name] = $handler instanceof JsExpression
            ? $handler
            : JsExpression::make($handler);

        return $this;
    }

    /**
     * 批量注册当前页面可复用的前端方法。
     * 调用方式与 method() 完全相同——模板/回调里可通过 `callPageMethod('name', ...)` 调用，
     * 也可模板裸调 `name(...)`（运行时已绑定到 Vue 实例，等价于 callPageMethod）。
     *
     * @param array<string, string|JsExpression> $methods 方法集合。
     * @return static 当前页面实例。
     */
    public function methods(array $methods): static
    {
        foreach ($methods as $name => $handler) {
            if (!is_string($name) || trim($name) === '') {
                continue;
            }

            if (!is_string($handler) && !$handler instanceof JsExpression) {
                continue;
            }

            $this->method($name, $handler);
        }

        return $this;
    }

    /**
     * 在页面加载时直接注入一个外部脚本(等价 V1 的 Html::js()->load())。
     *
     * 脚本以 <script src="..."> 形式放在页面 body 中，可用全局(如 window.AMap)直接在页面方法里使用；
     * 空 URL 忽略，重复 URL 去重（跨位置同样去重）。
     *
     * 默认($beforeRuntime = false)挂到 sc-v2 运行时之后并以 async 规范注入：不阻塞页面启动/挂载，
     * SDK 后台就绪，适合"用户点击时才用得到"的资源(地图 SDK、二维码库等)——函数体内用前先判空。
     * 传 true 放在运行时之前（普通同步脚本，会阻塞页面启动直到执行完），保证 Vue 挂载/运行时加载时
     * 全局必定可用，适合"页面初始化或运行时立即依赖它"的场景——代价是页面加载变慢，谨慎使用。
     *
     * 注意：两种位置都是 eager 加载——只要打开页面就会请求，哪怕对应功能未被触发；
     * 需要按需加载的场景请继续走页面方法内动态注入 <script> 的方式。
     *
     * @param string $url 外部脚本 URL。
     * @param bool $beforeRuntime true 时在 sc-v2 运行时之前加载（阻塞启动）。
     * @return static 当前页面实例。
     */
    public function loadScript(string $url, bool $beforeRuntime = false): static
    {
        $url = trim($url);
        if ($url === '') {
            return $this;
        }
        if (in_array($url, $this->externalScripts, true) || in_array($url, $this->externalScriptsBeforeRuntime, true)) {
            return $this;
        }

        $beforeRuntime
            ? $this->externalScriptsBeforeRuntime[] = $url
            : $this->externalScripts[] = $url;

        return $this;
    }

    /**
     * 在页面加载时直接注入一个外部样式表。
     *
     * 样式以 <link rel="stylesheet" href="..."> 形式放在页面 head 中；空 URL 忽略，重复 URL 去重。
     *
     * @param string $href 外部样式 URL。
     * @return static 当前页面实例。
     */
    public function loadStyle(string $href): static
    {
        $href = trim($href);
        $href !== '' && !in_array($href, $this->externalStyles, true) && $this->externalStyles[] = $href;

        return $this;
    }

    /**
     * 页面级外部脚本 URL 列表（运行时之后加载）。
     *
     * @return string[]
     */
    public function getExternalScripts(): array
    {
        return $this->externalScripts;
    }

    /**
     * 页面级运行时之前加载的外部脚本 URL 列表。
     *
     * @return string[]
     */
    public function getExternalScriptsBeforeRuntime(): array
    {
        return $this->externalScriptsBeforeRuntime;
    }

    /**
     * 页面级外部样式 URL 列表。
     *
     * @return string[]
     */
    public function getExternalStyles(): array
    {
        return $this->externalStyles;
    }

    /**
     * 显式挂载页面级托管弹窗。
     *
     * 手动打开弹窗必须走帧运行时接口（裸变量赋值无效）：
     * - 页面方法内：`ctx.openDialog('key')`（simple 运行时；list 页面对应 `ctx.vm.openHostDialog(...)`）
     * - 组件动作：`Actions::make(...)->dialog(...)` 或动作回调里 `vm.openDialog(...)`
     *
     * @param Dialog ...$dialogs 页面级弹窗。
     * @return static 当前页面实例。
     *
     * 示例：
     * - `Pages::make('问答信息')->dialogs(Dialogs::make('qa-info-dialog', '编辑问答'))`
     */
    public function dialogs(Dialog ...$dialogs): static
    {
        foreach ($dialogs as $dialog) {
            $this->dialogs[$dialog->key()] = $dialog;
        }

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getHeaderActions(): array
    {
        return array_values(array_filter(
            $this->headerActions,
            static fn (Action $action): bool => $action->isAvailable()
        ));
    }

    /**
     * @return array<int, string|AbstractHtmlElement|Renderable>
     */
    public function getHeaderContent(): array
    {
        return $this->headerContent;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getDialogs(): array
    {
        return array_values($this->resolveDialogs());
    }

    public function getDialog(string $key): ?Dialog
    {
        return $this->resolveDialogs()[$key] ?? null;
    }

    public function getTheme(): ?ThemeInterface
    {
        return $this->renderTheme;
    }

    public function getBackground(): string
    {
        return $this->background ?? self::BACKGROUND_PRESETS['white'];
    }

    public function hasCustomBackground(): bool
    {
        return $this->background !== null;
    }

    public function toHtml(?ThemeInterface $theme = null): string
    {
        $theme ??= $this->renderTheme ?? new ElementPlusAdminTheme();

        $context = new RenderContext($theme, new Document($this->title));
        $context->bootTheme();
        if ($this->hasCustomBackground()) {
            $context->document()->body()->setAttr('style', 'background:' . rtrim($this->getBackground(), ';') . ';');
        }

        $context->document()->mount($this->render($context));

        return $context->document()->toHtml();
    }

    protected static function normalizeKey(string $title): string
    {
        $key = strtolower($title);
        $key = preg_replace('/[^a-z0-9]+/', '-', $key);
        $key = trim($key ?: 'page', '-');

        return $key !== '' ? $key : 'page';
    }

    protected function resolveDialogs(): array
    {
        $dialogs = [];

        $this->collectDialogsFromActions($dialogs, $this->getHeaderActions());

        foreach ($this->definedDialogs() as $key => $dialog) {
            $dialogs[$key] = $dialog;
        }

        return $dialogs;
    }

    protected function definedDialogs(): array
    {
        return $this->dialogs;
    }

    /**
     * @param Action[] $actions
     */
    protected function collectDialogsFromActions(array &$dialogs, array $actions): void
    {
        foreach ($actions as $action) {
            if (!$action instanceof Action || !$action->isAvailable()) {
                continue;
            }

            $dialog = $action->getDialog();
            if ($dialog !== null) {
                $dialogs[$dialog->key()] ??= $dialog;
            }
        }
    }
}
