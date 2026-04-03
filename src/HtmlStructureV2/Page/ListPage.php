<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;

class ListPage extends AbstractPage
{
    private ?Form $filterForm = null;
    private ?Table $table = null;
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';

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

    public function deleteUrl(?string $deleteUrl): self
    {
        $this->deleteUrl = $deleteUrl;

        return $this;
    }

    public function deleteKey(string $deleteKey): self
    {
        $this->deleteKey = $deleteKey;

        return $this;
    }

    public function getFilterForm(): ?Form
    {
        return $this->filterForm;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getDeleteUrl(): ?string
    {
        return $this->deleteUrl;
    }

    public function getDeleteKey(): string
    {
        return $this->deleteKey;
    }

    protected function resolveDialogs(): array
    {
        $dialogs = [];

        $this->collectDialogsFromActions($dialogs, $this->getHeaderActions());

        if ($this->table !== null) {
            $this->collectDialogsFromActions($dialogs, $this->table->getToolbarActions());
            $this->collectDialogsFromActions($dialogs, $this->table->getRowActions());
        }

        foreach ($this->definedDialogs() as $key => $dialog) {
            $dialogs[$key] = $dialog;
        }

        return $dialogs;
    }
}
