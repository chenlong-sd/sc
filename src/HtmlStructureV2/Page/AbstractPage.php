<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\Document;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdminTheme;

abstract class AbstractPage implements Renderable
{
    use RendersWithTheme;

    private string $description = '';
    private array $headerActions = [];
    private array $sections = [];
    private array $dialogs = [];

    public function __construct(
        private readonly string $title,
        private readonly string $key
    ) {
    }

    public static function make(string $title, ?string $key = null): static
    {
        return new static($title, $key ?: static::normalizeKey($title));
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function actions(Action ...$actions): static
    {
        $this->headerActions = array_merge($this->headerActions, $actions);

        return $this;
    }

    public function addSection(Renderable ...$sections): static
    {
        $this->sections = array_merge($this->sections, $sections);

        return $this;
    }

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

    public function toHtml(?ThemeInterface $theme = null): string
    {
        $theme ??= new ElementPlusAdminTheme();

        $context = new RenderContext($theme, new Document($this->title));
        $context->bootTheme();

        $context->document()->mount($this->render($context));

        return $context->document()->toHtml();
    }

    protected static function normalizeKey(string $title): string
    {
        $key = strtolower($title);
        $key = preg_replace('/[^a-z0-9]+/', '-', $key);

        return trim($key ?: 'page', '-');
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
