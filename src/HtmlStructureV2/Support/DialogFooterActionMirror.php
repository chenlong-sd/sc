<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;

final class DialogFooterActionMirror
{
    public function __construct(
        private readonly StructuredEventInspector $structuredEventInspector = new StructuredEventInspector(),
    ) {
    }

    /**
     * 收集动作直接引用的弹窗，并把动作镜像到其 footer 目标。
     *
     * @param Action[] $actions
     * @param array<string, Dialog> $dialogs
     */
    public function collectAndApply(array $actions, array &$dialogs): void
    {
        foreach ($actions as $action) {
            if (!$action instanceof Action || !$action->isAvailable()) {
                continue;
            }

            $this->registerDialogs($dialogs, $action->getDialog());

            if ($action->hasEventHandlers()) {
                foreach ($this->structuredEventInspector->collectDialogsFromEventMap($action->getEventHandlers()) as $dialog) {
                    $this->registerDialogs($dialogs, $dialog);
                }
            }

            foreach ($action->getDialogFooterTargets() as $dialog) {
                $this->registerDialogs($dialogs, $dialog);
            }
        }

        foreach ($actions as $action) {
            if (!$action instanceof Action || !$action->isAvailable()) {
                continue;
            }

            foreach ($action->getDialogFooterTargets() as $dialogKey => $targetDialog) {
                $target = $dialogs[$dialogKey] ?? $targetDialog;
                if (!$target instanceof Dialog) {
                    continue;
                }

                $target->addMirroredFooterAction($action);
            }
        }
    }

    /**
     * @param array<string, Dialog> $dialogs
     */
    private function registerDialogs(array &$dialogs, ?Dialog $dialog): void
    {
        if (!$dialog instanceof Dialog) {
            return;
        }

        $key = $dialog->key();
        $current = $dialogs[$key] ?? null;
        if ($current instanceof Dialog && spl_object_id($current) !== spl_object_id($dialog)) {
            throw new InvalidArgumentException(sprintf('Duplicate V2 dialog key detected: %s', $key));
        }

        $dialogs[$key] = $dialog;
    }
}
