<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class ActionMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function supports(Renderable $component): bool
    {
        return $component instanceof Action;
    }

    public function dialogs(Renderable $component): array
    {
        if (!$component instanceof Action || !$component->isAvailable()) {
            return [];
        }

        return $this->dialogsFromActions([$component]);
    }

    public function actionCollections(Renderable $component): array
    {
        if (!$component instanceof Action || !$component->isAvailable()) {
            return [];
        }

        return [
            new ManagedActionCollection(
                [$component],
                sprintf('action [%s]', $component->label())
            ),
        ];
    }
}
