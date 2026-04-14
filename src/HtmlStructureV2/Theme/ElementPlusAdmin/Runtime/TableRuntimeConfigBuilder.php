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
            'remoteDataHandle' => $table->getRemoteDataHandle(),
            'rowKey' => $table->getRowKey(),
            'tree' => [
                'enabled' => $table->isTree(),
                'childrenKey' => $table->getTreeChildrenKey(),
                'props' => $table->getTreeProps(),
            ],
            'dragSort' => [
                'enabled' => $table->useDragSort(),
                'handleClass' => 'sc-v2-table-drag-handle',
                'label' => $table->getDragSortLabel(),
                'type' => $table->getDragSortType(),
                'icon' => $table->getDragSortIcon(),
                'options' => $table->getDragSortConfig(),
            ],
            'searchSchema' => $table->getSearchSchema(),
            'pagination' => [
                'enabled' => $table->usePagination(),
                'pageSize' => $table->getPageSize(),
                'pageSizes' => $table->getPageSizes(),
            ],
            'sortFieldMap' => $this->buildSortFieldMap($table),
            'deleteUrl' => $table->getDeleteUrl(),
            'deleteKey' => $table->getDeleteKey(),
            'trash' => [
                'enabled' => $table->useTrash(),
                'dialogKey' => $table->getTrashDialogKey(),
                'dialogTitle' => $table->getTrashDialogTitle(),
                'queryKey' => $table->getTrashQueryKey(),
                'queryValue' => $table->getTrashQueryValue(),
                'recoverUrl' => $table->getTrashRecoverUrl(),
            ],
            'maxHeight' => $table->getMaxHeight(),
            'settings' => [
                'enabled' => $table->useSettings(),
                'stripe' => $table->useStripe(),
                'border' => $table->useBorder(),
                'columns' => $this->buildSettingsColumns($table),
            ],
            'statusToggles' => [
                'items' => $this->buildStatusToggleItems($table),
                'newLine' => $table->useStatusTogglesNewLine(),
            ],
            'export' => [
                'enabled' => $table->useExport(),
                'filename' => $table->getExportFilename(),
                'query' => $table->getExportQuery(),
                'columns' => $this->buildExportColumns($table),
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
                'export' => ($column->getExportExcel()['allow'] ?? true) === true,
                'exportSort' => $column->getExportExcel()['sort'] ?? null,
            ];
        }

        return $columns;
    }

    private function buildExportColumns(Table $table): array
    {
        $columns = [];
        $position = 0;

        foreach ($table->columns() as $column) {
            if ($column->prop() === '' || $column->isSpecialColumn()) {
                continue;
            }

            $exportMeta = $column->getExportExcel();
            if (($exportMeta['allow'] ?? true) !== true) {
                continue;
            }

            $columns[] = [
                'key' => $column->prop(),
                'label' => $column->label() !== '' ? $column->label() : $column->prop(),
                'sort' => $exportMeta['sort'] ?? null,
                '_index' => $position++,
                'respectVisibility' => $column->supportsSettings(),
                'format' => $column->getFormat(),
                'display' => $column->getDisplay(),
            ];
        }

        usort($columns, static function (array $left, array $right): int {
            $leftSort = $left['sort'] ?? $left['_index'] ?? 0;
            $rightSort = $right['sort'] ?? $right['_index'] ?? 0;

            return $leftSort <=> $rightSort ?: (($left['_index'] ?? 0) <=> ($right['_index'] ?? 0));
        });

        foreach ($columns as &$column) {
            unset($column['_index']);
        }
        unset($column);

        return $columns;
    }

    private function buildStatusToggleItems(Table $table): array
    {
        $items = [];

        foreach ($table->getStatusToggles() as $toggle) {
            $name = is_string($toggle['name'] ?? null) ? trim($toggle['name']) : '';
            if ($name === '') {
                continue;
            }

            $items[] = [
                'name' => $name,
                'options' => array_values(is_array($toggle['options'] ?? null) ? $toggle['options'] : []),
            ];
        }

        return $items;
    }
}
