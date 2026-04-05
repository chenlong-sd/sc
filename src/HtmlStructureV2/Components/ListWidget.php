<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class ListWidget implements Renderable
{
    use HasEvents;
    use RendersWithTheme;

    private ?Form $filterForm = null;
    private ?Table $table = null;
    /** @var array<string, Dialog> */
    private array $dialogs = [];
    private string $filterTitle = '筛选条件';
    private bool $showSummary = true;

    public function __construct(
        private readonly string $key
    ) {
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function filters(Form $filterForm): self
    {
        $this->filterForm = $filterForm;

        return $this;
    }

    public function table(Table $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function dialogs(Dialog ...$dialogs): self
    {
        foreach ($dialogs as $dialog) {
            $this->dialogs[$dialog->key()] = $dialog;
        }

        return $this;
    }

    public function filterTitle(string $title): self
    {
        $this->filterTitle = $title;

        return $this;
    }

    public function showSummary(bool $showSummary = true): self
    {
        $this->showSummary = $showSummary;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getFilterForm(): ?Form
    {
        return $this->filterForm;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getFilterTitle(): string
    {
        return $this->filterTitle;
    }

    public function shouldShowSummary(): bool
    {
        return $this->showSummary;
    }

    /**
     * @return Dialog[]
     */
    public function getDialogs(): array
    {
        $dialogs = [];

        if ($this->table !== null) {
            $this->collectDialogsFromActions($dialogs, $this->table->getToolbarActions());
            $this->collectDialogsFromActions($dialogs, $this->table->getRowActions());
        }

        foreach ($this->dialogs as $key => $dialog) {
            $dialogs[$key] = $dialog;
        }

        return array_values($dialogs);
    }

    /**
     * @param Action[] $actions
     * @param array<string, Dialog> $dialogs
     */
    private function collectDialogsFromActions(array &$dialogs, array $actions): void
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
