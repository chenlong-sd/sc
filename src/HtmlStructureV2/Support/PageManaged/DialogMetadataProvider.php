<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\FormActionCollector;
use Sc\Util\HtmlStructureV2\Support\FormDialogCollector;

final class DialogMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function __construct(
        private readonly FormActionCollector $formActionCollector = new FormActionCollector(),
        private readonly FormDialogCollector $formDialogCollector = new FormDialogCollector(),
    ) {
    }

    public function supports(Renderable $component): bool
    {
        return $component instanceof Dialog;
    }

    public function dialogs(Renderable $component): array
    {
        if (!$component instanceof Dialog) {
            return [];
        }

        $dialogs = [];
        $visited = [];

        $this->collectDialogTree($component, $dialogs, $visited);

        return array_values($dialogs);
    }

    public function actionCollections(Renderable $component): array
    {
        if (!$component instanceof Dialog) {
            return [];
        }

        $collections = [];

        if ($component->getForm() !== null) {
            $collections[] = new ManagedActionCollection(
                $this->formActionCollector->collect($component->getForm()),
                sprintf('dialog [%s] form', $component->key())
            );
        }

        $collections[] = new ManagedActionCollection(
            $component->getFooterActions(),
            sprintf('dialog [%s] footer', $component->key())
        );

        return $collections;
    }

    /**
     * @param array<string, Dialog> $dialogs
     * @param array<string, true> $visited
     */
    private function collectDialogTree(Dialog $dialog, array &$dialogs, array &$visited): void
    {
        $visitKey = $dialog->key() . '#' . spl_object_id($dialog);
        if (isset($visited[$visitKey])) {
            return;
        }

        $visited[$visitKey] = true;
        $dialogs[$visitKey] = $dialog;

        foreach ($this->dialogsFromActions($dialog->getFooterActions()) as $nestedDialog) {
            $this->collectDialogTree($nestedDialog, $dialogs, $visited);
        }

        if ($dialog->getForm() === null) {
            return;
        }

        foreach ($this->dialogsFromActions($this->formActionCollector->collect($dialog->getForm())) as $nestedDialog) {
            $this->collectDialogTree($nestedDialog, $dialogs, $visited);
        }

        foreach ($this->formDialogCollector->collect($dialog->getForm()) as $nestedDialog) {
            $this->collectDialogTree($nestedDialog, $dialogs, $visited);
        }
    }
}
