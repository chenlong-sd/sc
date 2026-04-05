<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;

final class RenderableComponentWalker
{
    /**
     * @param Renderable[] $components
     */
    public function walk(array $components, callable $visitor): void
    {
        foreach ($components as $component) {
            $this->visit($component, $visitor);
        }
    }

    public function visit(Renderable $component, callable $visitor): void
    {
        $visitor($component);

        foreach ($this->childrenOf($component) as $child) {
            $this->visit($child, $visitor);
        }
    }

    /**
     * @return Renderable[]
     */
    private function childrenOf(Renderable $component): array
    {
        if ($component instanceof RenderableContainer) {
            return $component->renderChildren();
        }

        return [];
    }
}
