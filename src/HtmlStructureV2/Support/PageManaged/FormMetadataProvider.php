<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\FormActionCollector;

final class FormMetadataProvider implements MetadataProviderInterface
{
    use CollectsDialogsFromActions;

    public function __construct(
        private readonly FormActionCollector $formActionCollector = new FormActionCollector(),
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

        return $this->dialogsFromActions($this->formActionCollector->collect($component));
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
