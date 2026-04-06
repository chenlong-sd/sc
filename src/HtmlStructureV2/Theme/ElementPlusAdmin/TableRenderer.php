<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class TableRenderer
{
    public function __construct(
        private readonly ColumnRenderer $columnRenderer,
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function renderToolbar(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $toolbar = El::double('div')->addClass('sc-v2-toolbar');
        $left = El::double('div')->addClass('sc-v2-toolbar__actions');

        foreach ($table->getToolbarActions() as $action) {
            $left->append($this->actionButtonRenderer->render($action, false, 'default', $bindings, $renderContext));
        }

        $toolbar->append($left);

        return $toolbar;
    }

    public function renderTable(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $element = El::double('el-table')->setAttrs([
            ':data' => $bindings->rowsExpression(),
            'v-loading' => $bindings->loadingExpression(),
            ':stripe' => $table->useStripe() ? 'true' : 'false',
            ':border' => $table->useBorder() ? 'true' : 'false',
            'empty-text' => $table->getEmptyText(),
            'style' => 'width: 100%',
            '@selection-change' => $bindings->selectionChangeExpression(),
            '@sort-change' => $bindings->sortChangeExpression(),
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
            $element->append($this->renderRowActionColumn($table, $bindings, $renderContext));
        }

        return $element;
    }

    public function renderPagination(Table $table, TableRenderBindings $bindings): AbstractHtmlElement
    {
        return El::double('div')->setAttr('style', 'display:flex;justify-content:flex-end')
            ->append(
                El::double('el-pagination')->setAttrs([
                    'background' => '',
                    'layout' => 'total, sizes, prev, pager, next, jumper',
                    ':current-page' => $bindings->pageExpression(),
                    ':page-size' => $bindings->pageSizeExpression(),
                    ':page-sizes' => JsonExpressionEncoder::encodeCompact($table->getPageSizes()),
                    ':total' => $bindings->totalExpression(),
                    '@size-change' => $bindings->pageSizeChangeExpression(),
                    '@current-change' => $bindings->pageChangeExpression(),
                ])
            );
    }

    private function renderRowActionColumn(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $actionColumn = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'fixed' => 'right',
            'width' => max(120, count($table->getRowActions()) * 76),
        ]);

        $template = El::double('template')->setAttr('#default', 'scope');
        $actions = El::double('div')->addClass('sc-v2-row-actions');
        foreach ($table->getRowActions() as $action) {
            $actions->append($this->actionButtonRenderer->render($action, true, 'small', $bindings, $renderContext));
        }
        $template->append($actions);
        $actionColumn->append($template);

        return $actionColumn;
    }
}
