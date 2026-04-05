<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;

final class FormNodeWalker
{
    /**
     * @param FormNode[] $nodes
     */
    public function walk(array $nodes, callable $visitor): void
    {
        foreach ($nodes as $node) {
            $this->visit($node, $visitor);
        }
    }

    public function visit(FormNode $node, callable $visitor): void
    {
        $visitor($node);

        foreach ($this->childrenOf($node) as $child) {
            $this->visit($child, $visitor);
        }
    }

    /**
     * @return FormNode[]
     */
    private function childrenOf(FormNode $node): array
    {
        if ($node instanceof FormNodeContainer) {
            return $node->childNodes();
        }

        return [];
    }
}
