<?php

namespace Sc\Util\HtmlStructureV2\Support;

use ReflectionClass;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class FormCustomRenderableEventInspector
{
    public function __construct(
        private readonly FormNodeWalker $formNodeWalker = new FormNodeWalker(),
        private readonly RenderableComponentWalker $renderableComponentWalker = new RenderableComponentWalker(),
        private readonly StructuredEventInspector $structuredEventInspector = new StructuredEventInspector(),
    ) {
    }

    /**
     * @param array<int, mixed> $nodes
     * @return array<int, \Sc\Util\HtmlStructureV2\Components\Dialog>
     */
    public function collectDialogsFromNodes(array $nodes): array
    {
        $dialogs = [];

        $this->walkCustomRenderables($nodes, function (Renderable $renderable) use (&$dialogs): void {
            if (!$renderable instanceof EventAware || !$renderable->hasEventHandlers()) {
                return;
            }

            foreach ($this->structuredEventInspector->collectDialogsFromEventMap($renderable->getEventHandlers()) as $dialog) {
                $dialogs[$dialog->key()] = $dialog;
            }
        });

        return array_values($dialogs);
    }

    /**
     * @param array<int, mixed> $nodes
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    public function validateTargetsInNodes(
        array $nodes,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys,
        string $ownerPrefix
    ): void {
        $this->walkCustomRenderables($nodes, function (Renderable $renderable) use (
            $knownTableKeys,
            $knownListKeys,
            $knownDialogKeys,
            $ownerPrefix
        ): void {
            if (!$renderable instanceof EventAware || !$renderable->hasEventHandlers()) {
                return;
            }

            $this->structuredEventInspector->validateEventMap(
                $renderable->getEventHandlers(),
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                sprintf('%s %s', $ownerPrefix, $this->describeRenderable($renderable))
            );
        });
    }

    /**
     * @param array<int, mixed> $nodes
     */
    private function walkCustomRenderables(array $nodes, callable $visitor): void
    {
        $this->formNodeWalker->walk($nodes, function (mixed $node) use ($visitor): void {
            if (!$node instanceof CustomNode) {
                return;
            }

            $content = $node->content();
            if (!$content instanceof Renderable) {
                return;
            }

            $this->renderableComponentWalker->visit($content, $visitor);
        });
    }

    private function describeRenderable(Renderable $renderable): string
    {
        $shortName = (new ReflectionClass($renderable))->getShortName();

        return sprintf('custom renderable [%s]', $shortName);
    }
}
