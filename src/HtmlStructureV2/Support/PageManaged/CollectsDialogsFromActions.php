<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Support\StructuredEventInspector;

trait CollectsDialogsFromActions
{
    /**
     * @param array<int, mixed> $actions
     * @return Dialog[]
     */
    private function dialogsFromActions(array $actions): array
    {
        $dialogs = [];

        foreach ($actions as $action) {
            if ($action instanceof Action && $action->hasEventHandlers()) {
                foreach ((new StructuredEventInspector())->collectDialogsFromEventMap($action->getEventHandlers()) as $dialog) {
                    $dialogs[$dialog->key()] = $dialog;
                }
            }

            if (!$action instanceof DialogAction) {
                continue;
            }

            $dialog = $action->getDialog();
            if ($dialog === null) {
                continue;
            }

            $dialogs[$dialog->key()] = $dialog;
        }

        return array_values($dialogs);
    }
}
