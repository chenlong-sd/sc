<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class TableMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function supports(Renderable $component): bool
    {
        return $component instanceof Table;
    }

    public function dialogs(Renderable $component): array
    {
        if (!$component instanceof Table) {
            return [];
        }

        $dialogs = [];

        $trashDialog = $component->getTrashDialog();
        if ($trashDialog !== null) {
            $dialogs[$trashDialog->key()] = $trashDialog;
        }

        foreach ($component->columns() as $column) {
            $dialog = $column->managedOpenPageDialog($component->key());
            if ($dialog !== null) {
                $dialogs[$dialog->key()] = $dialog;
            }
        }

        foreach (array_merge(
            $this->dialogsFromActions($component->getToolbarLeftActions()),
            $this->dialogsFromActions($component->getToolbarRightActions()),
            $this->dialogsFromActions($component->getRowActions())
        ) as $dialog) {
            $dialogs[$dialog->key()] = $dialog;
        }

        return array_values($dialogs);
    }

    public function actionCollections(Renderable $component): array
    {
        if (!$component instanceof Table) {
            return [];
        }

        return [
            new ManagedActionCollection($component->getToolbarLeftActions(), sprintf('table [%s] toolbar left', $component->key())),
            new ManagedActionCollection($component->getToolbarRightActions(), sprintf('table [%s] toolbar right', $component->key())),
            new ManagedActionCollection($component->getRowActions(), sprintf('table [%s] row actions', $component->key())),
        ];
    }
}
