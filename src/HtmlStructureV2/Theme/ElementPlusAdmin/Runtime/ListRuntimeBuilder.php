<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Page\ListPage;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class ListRuntimeBuilder
{
    private ?DialogConfigBuilder $dialogConfigBuilder = null;

    public function build(ListPage $page): string
    {
        $table = $page->getTable();
        $dataSource = $table?->getDataSource();

        $config = JsonExpressionEncoder::encode([
            'title' => $page->title(),
            'filterDefaults' => $page->getFilterForm()?->defaults() ?? [],
            'searchSchema' => $table?->getSearchSchema() ?? [],
            'filterRules' => $page->getFilterForm()?->rules() ?? [],
            'filterRemoteOptions' => $page->getFilterForm()?->remoteOptions() ?? [],
            'filterSelectOptions' => $page->getFilterForm()?->selectOptions() ?? [],
            'filterLinkages' => $page->getFilterForm()?->linkages() ?? [],
            'filterUploads' => $page->getFilterForm()?->uploads() ?? [],
            'dialogs' => $this->buildDialogConfig($page->getDialogs()),
            'initialRows' => $dataSource?->initialRows() ?? [],
            'list' => $dataSource?->toClientConfig(),
            'deleteUrl' => $page->getDeleteUrl(),
            'deleteKey' => $page->getDeleteKey(),
            'pagination' => [
                'enabled' => $table?->usePagination() ?? false,
                'pageSize' => $table?->getPageSize() ?? 20,
                'pageSizes' => $table?->getPageSizes() ?? [10, 20, 50, 100],
            ],
            'sortFieldMap' => $this->getSortFieldMap($table),
        ]);

        return RuntimeScriptLoader::loadMany([
            'runtime-helpers.js',
            'request-action-factory.js',
            'form-runtime-factory.js',
            'managed-dialog-factory.js',
            'list/form-methods.js',
            'list/filter-methods.js',
            'list/table-methods.js',
            'list/dialog-methods.js',
            'list-runtime.js',
        ], [
            '__SC_V2_CONFIG__' => $config,
        ]);
    }

    private function buildDialogConfig(array $dialogs): array
    {
        return $this->dialogConfigBuilder()->build($dialogs);
    }

    private function getSortFieldMap(?Table $table): array
    {
        if ($table === null) {
            return [];
        }

        $map = [];
        foreach ($table->columns() as $column) {
            if ($column->isSortable()) {
                $map[$column->prop()] = $column->getSortField();
            }
        }

        return $map;
    }

    private function dialogConfigBuilder(): DialogConfigBuilder
    {
        return $this->dialogConfigBuilder ??= new DialogConfigBuilder();
    }
}
