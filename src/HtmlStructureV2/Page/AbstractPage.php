<?php

namespace Sc\Util\HtmlStructureV2\Page;

use InvalidArgumentException;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Contracts\DocumentRenderable;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\Conditionable;
use Sc\Util\HtmlStructureV2\Support\Document;
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

    private array $headerActions = [];
    private array $headerContent = [];
    private array $sections = [];
    private array $dialogs = [];
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
     */
    public static function make(string $title, ?string $key = null): static
    {
        return new static($title, $key ?: static::normalizeKey($title));
    }

    /**
     * 设置 HTML "<title>"。
     * 只影响浏览器标签标题和文档标题，不直接决定页面头部怎么展示。
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
     */
    public function header(string|AbstractHtmlElement|Renderable ...$content): static
    {
        $this->headerContent = $content;

        return $this;
    }

    /**
     * 设置页面根容器背景。
     * 支持任意 CSS "background" 值，例如颜色、渐变或图片。
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
     */
    public function theme(ThemeInterface $theme): static
    {
        $this->renderTheme = $theme;

        return $this;
    }

    /**
     * 设置页面头部动作按钮。
     */
    public function actions(Action ...$actions): static
    {
        $this->headerActions = array_merge($this->headerActions, $actions);

        return $this;
    }

    /**
     * 向页面主体追加一个或多个区块。
     */
    public function addSection(Renderable ...$sections): static
    {
        $this->sections = array_merge($this->sections, $sections);

        return $this;
    }

    /**
     * 显式挂载页面级托管弹窗。
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

            if (!$action instanceof DialogAction) {
                continue;
            }

            $dialog = $action->getDialog();
            if ($dialog === null) {
                continue;
            }

            $dialogs[$dialog->key()] ??= $dialog;
        }
    }
}
