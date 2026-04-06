<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\DialogConfigBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\TableRuntimeConfigBuilder;

final class RuntimePreparationCoordinator
{
    public function __construct(
        private readonly FormRenderStateFactory $formRenderStateFactory = new FormRenderStateFactory(),
        private readonly TableRenderStateFactory $tableRenderStateFactory = new TableRenderStateFactory(),
        private readonly DialogConfigBuilder $dialogConfigBuilder = new DialogConfigBuilder(),
        private readonly TableRuntimeConfigBuilder $tableRuntimeConfigBuilder = new TableRuntimeConfigBuilder(),
    ) {
    }

    public function registerSimpleFormRuntime(
        PageRuntimeRegistry $registry,
        FormRenderState $state,
        Form $form
    ): void {
        $registry->mergeSimpleState($state->simpleRuntimeState($form));
        $registry->registerFormConfig($state->scope->value(), $state->simpleRuntimeConfig($form));
    }

    public function prepareListWidget(
        PageRuntimeRegistry $registry,
        ListWidget $list,
        array $tableOverrides = [],
        bool $primary = false
    ): PreparedListWidget {
        $filterState = null;
        $filterForm = $list->resolveFilterForm();
        if ($filterForm !== null) {
            $filterState = $this->formRenderStateFactory->createListFilter($list->key());
            $this->registerSimpleFormRuntime($registry, $filterState, $filterForm);
        }

        $tableState = null;
        $table = $list->getTable();
        if ($table !== null) {
            $tableState = $this->prepareTable(
                $registry,
                $table,
                array_replace(['listKey' => $list->key()], $tableOverrides)
            );
        }

        $registry->registerList($list, [
            'key' => $list->key(),
            'filterScope' => $filterState?->scope->value(),
            'tableKey' => $table?->key(),
            'events' => $list->getEventHandlers(),
        ]);
        $registry->requireListRuntime($primary ? $list->key() : null);

        return new PreparedListWidget(
            filterForm: $filterForm,
            filterState: $filterState,
            tableState: $tableState,
            dialogs: $this->prepareManagedDialogs($registry, $list->getDialogs()),
        );
    }

    public function prepareTable(
        PageRuntimeRegistry $registry,
        Table $table,
        array $configOverrides = []
    ): TableRenderState {
        $state = $this->tableRenderStateFactory->create($table->key());
        $registry->registerTable(
            $table,
            $this->tableRuntimeConfigBuilder->build($table, $configOverrides)
        );

        return $state;
    }

    /**
     * @param Dialog[] $dialogs
     * @return Dialog[]
     */
    public function prepareManagedDialogs(PageRuntimeRegistry $registry, array $dialogs): array
    {
        $registered = [];

        foreach ($dialogs as $dialog) {
            if (!$dialog instanceof Dialog || !$registry->registerManagedDialog($dialog)) {
                continue;
            }

            $registered[] = $dialog;
        }

        if ($registered === []) {
            return [];
        }

        $registry->mergeSimpleConfig([
            'dialogs' => $this->dialogConfigBuilder->build($registered),
        ]);

        foreach ($registered as $dialog) {
            if ($dialog->getForm() === null) {
                continue;
            }

            $this->registerSimpleFormRuntime(
                $registry,
                $this->formRenderStateFactory->createManagedDialog($dialog->key()),
                $dialog->getForm()
            );
        }

        return $registered;
    }
}
