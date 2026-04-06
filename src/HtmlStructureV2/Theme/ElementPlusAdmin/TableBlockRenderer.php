<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\RenderContext;

final class TableBlockRenderer
{
    public function __construct(
        private readonly TableRenderer $tableRenderer,
    ) {
    }

    public function render(
        Table $table,
        TableRenderBindings $bindings,
        bool $showSummary = true,
        ?RenderContext $renderContext = null
    ): DoubleLabel
    {
        $block = El::double('div')->addClass('sc-v2-table-block');

        if ($table->getToolbarActions()) {
            $block->append($this->tableRenderer->renderToolbar($table, $bindings, $renderContext));
        }

        $block->append($this->tableRenderer->renderTable($table, $bindings, $renderContext));

        if ($table->usePagination()) {
            $block->append($this->tableRenderer->renderPagination($table, $bindings));
        }

        if ($showSummary) {
            $block->append(
                El::double('div')->addClass('sc-v2-table__footer')->append(
                    El::double('span')->append(sprintf('共 {{ %s }} 条数据', $bindings->totalExpression()))
                )
            );
        }

        return $block;
    }
}
