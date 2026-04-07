<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\Components\Table;

final class TableRuntimeConfigBuilder
{
    public function build(Table $table, array $overrides = []): array
    {
        return array_replace_recursive([
            'key' => $table->key(),
            'initialRows' => $table->getDataSource()?->initialRows() ?? [],
            'dataSource' => $table->getDataSource()?->toClientConfig(),
            'searchSchema' => $table->getSearchSchema(),
            'pagination' => [
                'enabled' => $table->usePagination(),
                'pageSize' => $table->getPageSize(),
                'pageSizes' => $table->getPageSizes(),
            ],
            'sortFieldMap' => $this->buildSortFieldMap($table),
            'deleteUrl' => $table->getDeleteUrl(),
            'deleteKey' => $table->getDeleteKey(),
            'maxHeight' => $table->getMaxHeight(),
            'settings' => [
                'enabled' => $table->useSettings(),
                'stripe' => $table->useStripe(),
                'border' => $table->useBorder(),
                'columns' => $this->buildSettingsColumns($table),
            ],
            'events' => $table->getEventHandlers(),
        ], $overrides);
    }

    private function buildSortFieldMap(Table $table): array
    {
        $map = [];

        foreach ($table->columns() as $column) {
            if (!$column->isSortable() || !$column->supportsSettings()) {
                continue;
            }

            $map[$column->prop()] = $column->getSortField();
        }

        return $map;
    }

    private function buildSettingsColumns(Table $table): array
    {
        $columns = [];

        foreach ($table->columns() as $column) {
            if (!$column->supportsSettings()) {
                continue;
            }

            $columns[] = [
                'key' => $column->prop(),
                'label' => $column->label() !== '' ? $column->label() : $column->prop(),
                'show' => true,
                'width' => $column->getWidth(),
                'fixed' => $column->getFixed(),
                'align' => $column->getAlign(),
            ];
        }

        return $columns;
    }
}
