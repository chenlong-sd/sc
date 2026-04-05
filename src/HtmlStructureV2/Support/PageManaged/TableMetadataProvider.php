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

        foreach (array_merge(
            $this->dialogsFromActions($component->getToolbarActions()),
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
            new ManagedActionCollection($component->getToolbarActions(), sprintf('table [%s] toolbar', $component->key())),
            new ManagedActionCollection($component->getRowActions(), sprintf('table [%s] row actions', $component->key())),
        ];
    }
}
