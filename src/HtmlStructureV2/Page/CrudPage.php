<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;

final class CrudPage extends AdminPage
{
    private ?Form $searchForm = null;
    private ?Table $table = null;
    private ?Dialog $editorDialog = null;
    private ?string $saveUrl = null;
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';

    public function search(Form $searchForm): self
    {
        $this->searchForm = $searchForm;

        return $this;
    }

    public function table(Table $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function editor(Dialog $editorDialog): self
    {
        $this->editorDialog = $editorDialog;

        return $this;
    }

    public function saveUrl(?string $saveUrl): self
    {
        $this->saveUrl = $saveUrl;

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

    public function getSearchForm(): ?Form
    {
        return $this->searchForm;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getEditorDialog(): ?Dialog
    {
        return $this->editorDialog;
    }

    public function getSaveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function getDeleteUrl(): ?string
    {
        return $this->deleteUrl;
    }

    public function getDeleteKey(): string
    {
        return $this->deleteKey;
    }
}
