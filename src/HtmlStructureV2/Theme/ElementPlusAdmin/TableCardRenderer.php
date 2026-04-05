<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Table;

final class TableCardRenderer
{
    public function __construct(
        private readonly TableRenderer $tableRenderer,
        private readonly SectionCardFactory $sectionCardFactory,
    ) {
    }

    public function render(Table $table, TableRenderBindings $bindings, bool $showSummary = true): DoubleLabel
    {
        $card = $this->sectionCardFactory->make();

        if ($table->getToolbarActions()) {
            $card->append($this->tableRenderer->renderToolbar($table, $bindings));
        }

        $card->append($this->tableRenderer->renderTable($table, $bindings));

        if ($table->usePagination()) {
            $card->append($this->tableRenderer->renderPagination($table, $bindings));
        }

        if ($showSummary) {
            $card->append(
                El::double('div')->addClass('sc-v2-table__footer')->append(
                    El::double('span')->append(sprintf('共 {{ %s }} 条数据', $bindings->totalExpression()))
                )
            );
        }

        return $card;
    }
}
