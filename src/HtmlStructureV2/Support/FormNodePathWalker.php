<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Contracts\FormNode;

final class FormNodePathWalker
{
    /**
     * @param FormNode[] $nodes
     */
    public function walk(array $nodes, callable $visitor, ?FormNodePathContext $context = null): void
    {
        $context ??= FormNodePathContext::root();

        foreach ($nodes as $node) {
            $this->visit($node, $context, $visitor);
        }
    }

    public function visit(FormNode $node, FormNodePathContext $context, callable $visitor): void
    {
        $visitor($node, $context);

        foreach ($this->childrenOf($node, $context) as [$children, $childContext]) {
            $this->walk($children, $visitor, $childContext);
        }
    }

    /**
     * @return array<int, array{0: FormNode[], 1: FormNodePathContext}>
     */
    private function childrenOf(FormNode $node, FormNodePathContext $context): array
    {
        if ($node instanceof FormNodePathScopedContainer) {
            return [[$node->childNodes(), $node->childPathContext($context)]];
        }

        return [];
    }
}
