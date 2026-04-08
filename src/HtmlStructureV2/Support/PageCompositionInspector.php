<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use ReflectionClass;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\Support\PageManaged\ManagedActionCollection;
use Sc\Util\HtmlStructureV2\Support\PageManaged\MetadataRegistry;

final class PageCompositionInspector
{
    public function __construct(
        private readonly RenderableComponentWalker $renderableComponentWalker = new RenderableComponentWalker(),
        private readonly ActionTargetValidator $actionTargetValidator = new ActionTargetValidator(),
        private readonly MetadataRegistry $metadataRegistry = new MetadataRegistry(),
        private readonly StructuredEventInspector $structuredEventInspector = new StructuredEventInspector(),
        private readonly FormCustomRenderableEventInspector $formCustomRenderableEventInspector = new FormCustomRenderableEventInspector(),
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
            $this->registerCollectedDialogs($dialogs, $this->collectRenderableManagedDialogs($dialog));
        }

        $this->renderableComponentWalker->walk(
            $components,
            function (Renderable $component) use (&$dialogs): void {
                $this->registerCollectedDialogs($dialogs, $this->collectRenderableManagedDialogs($component));
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
            fn(Renderable $component) => $this->validateRenderableTargets(
                $component,
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys
            )
        );

        foreach ($dialogs as $dialog) {
            $this->validateRenderableTargets(
                $dialog,
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
            );
        }
    }

    /**
     * @param ManagedActionCollection[] $collections
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    private function validateActionCollections(
        array $collections,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys
    ): void
    {
        foreach ($collections as $collection) {
            $this->actionTargetValidator->validate(
                $collection->actions,
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                $collection->owner
            );
        }
    }

    /**
     * @param array<string, Dialog> $dialogs
     */
    private function registerCollectedDialogs(array &$dialogs, array $collectedDialogs): void
    {
        foreach ($collectedDialogs as $dialog) {
            $this->registerCollectedDialog($dialogs, $dialog);
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
        $this->registerCollectedDialogs($dialogs, $this->collectRenderableManagedDialogs($dialog));
    }

    /**
     * @return Dialog[]
     */
    private function collectRenderableManagedDialogs(Renderable $component): array
    {
        if ($component instanceof Action && !$component->isAvailable()) {
            return [];
        }

        $dialogs = [];
        $this->mergeCollectedDialogs($dialogs, $this->metadataRegistry->dialogs($component));

        if ($component instanceof EventAware && $component->hasEventHandlers()) {
            $this->mergeCollectedDialogs(
                $dialogs,
                $this->structuredEventInspector->collectDialogsFromEventMap($component->getEventHandlers())
            );
        }

        if ($component instanceof Form) {
            $this->mergeCollectedDialogs(
                $dialogs,
                $this->formCustomRenderableEventInspector->collectDialogsFromNodes($component->children())
            );
        } elseif ($component instanceof Dialog && $component->getForm() !== null) {
            $this->mergeCollectedDialogs(
                $dialogs,
                $this->formCustomRenderableEventInspector->collectDialogsFromNodes($component->getForm()->children())
            );
        }

        return array_values($dialogs);
    }

    /**
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    private function validateRenderableTargets(
        Renderable $component,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys
    ): void {
        if ($component instanceof Action && !$component->isAvailable()) {
            return;
        }

        $this->validateActionCollections(
            $this->metadataRegistry->actionCollections($component),
            $knownTableKeys,
            $knownListKeys,
            $knownDialogKeys
        );

        if ($component instanceof EventAware && $component->hasEventHandlers()) {
            $this->structuredEventInspector->validateEventMap(
                $component->getEventHandlers(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                $this->describeRenderableOwner($component)
            );
        }

        if ($component instanceof Form) {
            $this->formCustomRenderableEventInspector->validateTargetsInNodes(
                $component->children(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('form [%s]', $component->key())
            );
        } elseif ($component instanceof Dialog && $component->getForm() !== null) {
            $this->formCustomRenderableEventInspector->validateTargetsInNodes(
                $component->getForm()->children(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('dialog [%s] form', $component->key())
            );
        }
    }

    private function describeRenderableOwner(Renderable $component): string
    {
        return match (true) {
            $component instanceof Action => sprintf('action [%s]', $component->label()),
            $component instanceof Form => sprintf('form [%s]', $component->key()),
            $component instanceof Table => sprintf('table [%s]', $component->key()),
            $component instanceof ListWidget => sprintf('list [%s]', $component->key()),
            $component instanceof Dialog => sprintf('dialog [%s]', $component->key()),
            default => sprintf(
                'renderable [%s]',
                (new ReflectionClass($component))->getShortName()
            ),
        };
    }

    /**
     * @param array<string, Dialog> $dialogs
     * @param Dialog[] $collectedDialogs
     */
    private function mergeCollectedDialogs(array &$dialogs, array $collectedDialogs): void
    {
        foreach ($collectedDialogs as $dialog) {
            $current = $dialogs[$dialog->key()] ?? null;
            if ($current instanceof Dialog) {
                if (spl_object_id($current) !== spl_object_id($dialog)) {
                    throw new InvalidArgumentException(sprintf('Duplicate V2 dialog key detected: %s', $dialog->key()));
                }

                continue;
            }

            $dialogs[$dialog->key()] = $dialog;
        }
    }
}
