<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class TableRenderer
{
    public function __construct(
        private readonly ColumnRenderer $columnRenderer,
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function renderToolbar(Table $table): AbstractHtmlElement
    {
        $toolbar = El::double('div')->addClass('sc-v2-toolbar');
        $left = El::double('div')->addClass('sc-v2-toolbar__actions');

        foreach ($table->getToolbarActions() as $action) {
            $left->append($this->actionButtonRenderer->render($action));
        }

        $toolbar->append($left);

        return $toolbar;
    }

    public function renderTable(Table $table, string $rowsName = 'tableRows', string $loadingName = 'tableLoading'): AbstractHtmlElement
    {
        $element = El::double('el-table')->setAttrs([
            ':data' => $rowsName,
            'v-loading' => $loadingName,
            ':stripe' => $table->useStripe() ? 'true' : 'false',
            ':border' => $table->useBorder() ? 'true' : 'false',
            'empty-text' => $table->getEmptyText(),
            'style' => 'width: 100%',
            '@selection-change' => 'handleSelectionChange',
            '@sort-change' => 'handleSortChange',
        ]);

        if ($table->hasSelection()) {
            $element->append(
                El::double('el-table-column')->setAttrs([
                    'type' => 'selection',
                    'width' => '48',
                    'align' => 'center',
                ])
            );
        }

        foreach ($table->columns() as $column) {
            $element->append($this->columnRenderer->render($column));
        }

        if ($table->getRowActions()) {
            $element->append($this->renderRowActionColumn($table));
        }

        return $element;
    }

    public function renderPagination(Table $table): AbstractHtmlElement
    {
        return El::double('div')->setAttr('style', 'display:flex;justify-content:flex-end')
            ->append(
                El::double('el-pagination')->setAttrs([
                    'background' => '',
                    'layout' => 'total, sizes, prev, pager, next, jumper',
                    ':current-page' => 'tablePage',
                    ':page-size' => 'tablePageSize',
                    ':page-sizes' => JsonExpressionEncoder::encode($table->getPageSizes()),
                    ':total' => 'tableTotal',
                    '@size-change' => 'handlePageSizeChange',
                    '@current-change' => 'handlePageChange',
                ])
            );
    }

    private function renderRowActionColumn(Table $table): AbstractHtmlElement
    {
        $actionColumn = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'fixed' => 'right',
            'width' => max(120, count($table->getRowActions()) * 76),
        ]);

        $template = El::double('template')->setAttr('#default', 'scope');
        $actions = El::double('div')->addClass('sc-v2-row-actions');
        foreach ($table->getRowActions() as $action) {
            $actions->append($this->actionButtonRenderer->render($action, true, 'small'));
        }
        $template->append($actions);
        $actionColumn->append($template);

        return $actionColumn;
    }
}
