<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\FormActionCollector;

final class ListMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function __construct(
        private readonly FormActionCollector $formActionCollector = new FormActionCollector(),
    ) {
    }

    public function supports(Renderable $component): bool
    {
        return $component instanceof ListWidget;
    }

    public function dialogs(Renderable $component): array
    {
        if (!$component instanceof ListWidget) {
            return [];
        }

        $dialogs = [];

        if ($component->getFilterForm() !== null) {
            foreach ($this->dialogsFromActions($this->formActionCollector->collect($component->getFilterForm())) as $dialog) {
                $dialogs[$dialog->key()] = $dialog;
            }
        }

        $table = $component->getTable();
        if ($table !== null) {
            foreach ($table->columns() as $column) {
                $dialog = $column->managedOpenPageDialog($table->key());
                if ($dialog !== null) {
                    $dialogs[$dialog->key()] = $dialog;
                }
            }
        }

        foreach ($component->getDialogs() as $dialog) {
            $dialogs[$dialog->key()] = $dialog;
        }

        return array_values($dialogs);
    }

    public function actionCollections(Renderable $component): array
    {
        if (!$component instanceof ListWidget) {
            return [];
        }

        $collections = [];

        if ($component->getFilterForm() !== null) {
            $collections[] = new ManagedActionCollection(
                $this->formActionCollector->collect($component->getFilterForm()),
                sprintf('list [%s] filters', $component->key())
            );
        }

        $table = $component->getTable();
        if ($table !== null) {
            $collections[] = new ManagedActionCollection(
                $table->getToolbarActions(),
                sprintf('list [%s] toolbar', $component->key())
            );
            $collections[] = new ManagedActionCollection(
                $table->getRowActions(),
                sprintf('list [%s] row actions', $component->key())
            );
        }

        return $collections;
    }
}
