<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Fields\PickerField;
use Sc\Util\HtmlStructureV2\Components\Form;

final class FormDialogCollector
{
    public function __construct(
        private readonly FormNodeWalker $formNodeWalker = new FormNodeWalker(),
    ) {
    }

    /**
     * @return Dialog[]
     */
    public function collect(Form $form): array
    {
        return $this->collectFromNodes($form->children());
    }

    /**
     * @param array<int, mixed> $nodes
     * @return Dialog[]
     */
    public function collectFromNodes(array $nodes): array
    {
        $dialogs = [];

        $this->formNodeWalker->walk($nodes, static function (mixed $node) use (&$dialogs): void {
            if (!$node instanceof PickerField) {
                return;
            }

            $dialog = $node->getDialog();
            if ($dialog === null) {
                return;
            }

            $dialogs[$dialog->key()] = $dialog;
        });

        return array_values($dialogs);
    }
}
