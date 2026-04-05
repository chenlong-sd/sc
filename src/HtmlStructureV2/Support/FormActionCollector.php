<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;

final class FormActionCollector
{
    public function __construct(
        private readonly FormNodeWalker $formNodeWalker = new FormNodeWalker(),
    ) {
    }

    public function collect(Form $form): array
    {
        return $this->collectFromNodes($form->children());
    }

    public function collectFromNodes(array $nodes): array
    {
        $actions = [];

        $this->formNodeWalker->walk($nodes, static function (mixed $node) use (&$actions): void {
            if (!$node instanceof Field) {
                return;
            }

            foreach ($node->getSuffixActions() as $action) {
                $actions[] = $action;
            }
        });

        return $actions;
    }
}
