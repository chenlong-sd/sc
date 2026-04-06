<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;

class ListPage extends AbstractPage
{
    private ?Form $filterForm = null;
    private ?Table $table = null;
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';

    /**
     * 设置页面筛选表单。
     */
    public function filters(Form $filterForm): self
    {
        $this->filterForm = $filterForm;

        return $this;
    }

    /**
     * 设置页面主表格。
     */
    public function table(Table $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * 设置页面级删除接口地址。
     */
    public function deleteUrl(?string $deleteUrl): self
    {
        $this->deleteUrl = $deleteUrl;

        return $this;
    }

    /**
     * 设置页面级删除主键字段名。
     */
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

    public function toListWidget(): ListWidget
    {
        $widget = ListWidget::make($this->key());

        if ($this->filterForm !== null) {
            $widget->filters($this->filterForm);
        }

        if ($this->table !== null) {
            $widget->table($this->table);
        }

        return $widget->dialogs(...$this->getDialogs());
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
