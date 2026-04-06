<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Contracts\DocumentRenderable;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\Document;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdminTheme;

abstract class AbstractPage implements DocumentRenderable, Renderable
{
    use RendersWithTheme;

    private string $description = '';
    private array $headerActions = [];
    private array $sections = [];
    private array $dialogs = [];
    private ?ThemeInterface $renderTheme = null;

    public function __construct(
        private readonly string $title,
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个页面实例，未传 key 时会按标题自动生成。
     */
    public static function make(string $title, ?string $key = null): static
    {
        return new static($title, $key ?: static::normalizeKey($title));
    }

    /**
     * 设置页面副标题/说明文案。
     */
    public function description(string $description): static
    {
        $this->description = $description;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHeaderActions(): array
    {
        return $this->headerActions;
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

    public function toHtml(?ThemeInterface $theme = null): string
    {
        $theme ??= $this->renderTheme ?? new ElementPlusAdminTheme();

        $context = new RenderContext($theme, new Document($this->title));
        $context->bootTheme();

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
