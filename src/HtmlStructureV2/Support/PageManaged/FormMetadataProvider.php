<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\FormActionCollector;
use Sc\Util\HtmlStructureV2\Support\FormDialogCollector;

final class FormMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function __construct(
        private readonly FormActionCollector $formActionCollector = new FormActionCollector(),
        private readonly FormDialogCollector $formDialogCollector = new FormDialogCollector(),
    ) {
    }

    public function supports(Renderable $component): bool
    {
        return $component instanceof Form;
    }

    public function dialogs(Renderable $component): array
    {
        if (!$component instanceof Form) {
            return [];
        }

        $dialogs = [];

        foreach ($this->dialogsFromActions($this->formActionCollector->collect($component)) as $dialog) {
            $dialogs[$dialog->key()] = $dialog;
        }

        foreach ($this->formDialogCollector->collect($component) as $dialog) {
            $dialogs[$dialog->key()] = $dialog;
        }

        return array_values($dialogs);
    }

    public function actionCollections(Renderable $component): array
    {
        if (!$component instanceof Form) {
            return [];
        }

        return [
            new ManagedActionCollection(
                $this->formActionCollector->collect($component),
                sprintf('form [%s]', $component->key())
            ),
        ];
    }
}
