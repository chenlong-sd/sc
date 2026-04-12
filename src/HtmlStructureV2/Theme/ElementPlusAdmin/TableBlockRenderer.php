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
        ?RenderContext $renderContext = null
    ): DoubleLabel
    {
        $block = El::double('div')->addClass('sc-v2-table-block');

        if ($table->getStatusToggles() !== []) {
            $block->append($this->tableRenderer->renderStatusToggleBar($table, $bindings));
        }

        if ($table->getToolbarLeftActions() || $table->getToolbarRightActions() || $table->useTrash() || $table->useExport() || $table->useSettings()) {
            $block->append($this->tableRenderer->renderToolbar($table, $bindings, $renderContext));
        }

        $block->append($this->tableRenderer->renderTable($table, $bindings, $renderContext));

        if ($table->usePagination()) {
            $block->append($this->tableRenderer->renderPagination($table, $bindings));
        }

        if ($table->useSettings()) {
            $block->append($this->tableRenderer->renderSettingsDialog($table, $bindings));
        }

        return $block;
    }
}
