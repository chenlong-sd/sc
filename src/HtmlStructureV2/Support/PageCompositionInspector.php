<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;

final class PageCompositionInspector
{
    public function __construct(
        private readonly RenderableComponentWalker $renderableComponentWalker = new RenderableComponentWalker(),
        private readonly FormActionCollector $formActionCollector = new FormActionCollector(),
        private readonly ActionTargetValidator $actionTargetValidator = new ActionTargetValidator(),
    ) {
    }

    /**
     * @param Renderable[] $components
     * @return Dialog[]
     */
    public function collectManagedDialogs(AbstractPage $page, array $components): array
    {
        $dialogs = [];

        foreach ($page->getDialogs() as $dialog) {
            $this->registerCollectedDialog($dialogs, $dialog);
        }

        $this->renderableComponentWalker->walk(
            $components,
            function (Renderable $component) use (&$dialogs): void {
                $this->collectDialogsFromComponent($dialogs, $component);
            }
        );

        return array_values($dialogs);
    }

    /**
     * @param Renderable[] $components
     * @param Dialog[] $dialogs
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     */
    public function validateActionTargets(
        AbstractPage $page,
        array $components,
        array $dialogs,
        array $knownTableKeys,
        array $knownListKeys
    ): void {
        $knownDialogKeys = array_map(
            static fn(Dialog $dialog): string => $dialog->key(),
            $dialogs
        );

        $this->actionTargetValidator->validate(
            $page->getHeaderActions(),
            $knownTableKeys,
            $knownListKeys,
            $knownDialogKeys,
            sprintf('page [%s] header', $page->key())
        );

        $this->renderableComponentWalker->walk(
            $components,
            fn(Renderable $component) => $this->validateComponentActionTargets(
                $component,
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys
            )
        );

        foreach ($dialogs as $dialog) {
            if ($dialog->getForm() !== null) {
                $this->actionTargetValidator->validate(
                    $this->formActionCollector->collect($dialog->getForm()),
                    $knownTableKeys,
                    $knownListKeys,
                    $knownDialogKeys,
                    sprintf('dialog [%s] form', $dialog->key())
                );
            }

            $this->actionTargetValidator->validate(
                $dialog->getFooterActions(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('dialog [%s] footer', $dialog->key())
            );
        }
    }

    /**
     * @param array<string, Dialog> $dialogs
     */
    private function collectDialogsFromComponent(array &$dialogs, Renderable $component): void
    {
        if ($component instanceof Form) {
            $this->collectDialogsFromActions($dialogs, $this->formActionCollector->collect($component));

            return;
        }

        if ($component instanceof ListWidget) {
            if ($component->getFilterForm() !== null) {
                $this->collectDialogsFromActions($dialogs, $this->formActionCollector->collect($component->getFilterForm()));
            }

            foreach ($component->getDialogs() as $dialog) {
                $this->registerCollectedDialog($dialogs, $dialog);
            }

            return;
        }

        if ($component instanceof Table) {
            $this->collectDialogsFromActions($dialogs, $component->getToolbarActions());
            $this->collectDialogsFromActions($dialogs, $component->getRowActions());

            return;
        }

        if ($component instanceof Dialog) {
            $this->registerCollectedDialog($dialogs, $component);
        }
    }

    /**
     * @param array<string, Dialog> $dialogs
     * @param array<int, mixed> $actions
     */
    private function collectDialogsFromActions(array &$dialogs, array $actions): void
    {
        foreach ($actions as $action) {
            if (!$action instanceof DialogAction) {
                continue;
            }

            $dialog = $action->getDialog();
            if ($dialog === null) {
                continue;
            }

            $this->registerCollectedDialog($dialogs, $dialog);
        }
    }

    /**
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    private function validateComponentActionTargets(
        Renderable $component,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys
    ): void {
        if ($component instanceof Form) {
            $this->actionTargetValidator->validate(
                $this->formActionCollector->collect($component),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('form [%s]', $component->key())
            );

            return;
        }

        if ($component instanceof ListWidget) {
            if ($component->getFilterForm() !== null) {
                $this->actionTargetValidator->validate(
                    $this->formActionCollector->collect($component->getFilterForm()),
                    $knownTableKeys,
                    $knownListKeys,
                    $knownDialogKeys,
                    sprintf('list [%s] filters', $component->key())
                );
            }

            $table = $component->getTable();
            if ($table !== null) {
                $this->actionTargetValidator->validate(
                    $table->getToolbarActions(),
                    $knownTableKeys,
                    $knownListKeys,
                    $knownDialogKeys,
                    sprintf('list [%s] toolbar', $component->key())
                );
                $this->actionTargetValidator->validate(
                    $table->getRowActions(),
                    $knownTableKeys,
                    $knownListKeys,
                    $knownDialogKeys,
                    sprintf('list [%s] row actions', $component->key())
                );
            }

            return;
        }

        if ($component instanceof Table) {
            $this->actionTargetValidator->validate(
                $component->getToolbarActions(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('table [%s] toolbar', $component->key())
            );
            $this->actionTargetValidator->validate(
                $component->getRowActions(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('table [%s] row actions', $component->key())
            );
        }
    }

    /**
     * @param array<string, Dialog> $dialogs
     */
    private function registerCollectedDialog(array &$dialogs, Dialog $dialog): void
    {
        $current = $dialogs[$dialog->key()] ?? null;
        if ($current instanceof Dialog) {
            if (spl_object_id($current) !== spl_object_id($dialog)) {
                throw new InvalidArgumentException(sprintf('Duplicate V2 dialog key detected: %s', $dialog->key()));
            }

            return;
        }

        $dialogs[$dialog->key()] = $dialog;
        $this->collectDialogsFromActions($dialogs, $dialog->getFooterActions());

        if ($dialog->getForm() !== null) {
            $this->collectDialogsFromActions($dialogs, $this->formActionCollector->collect($dialog->getForm()));
        }
    }
}
