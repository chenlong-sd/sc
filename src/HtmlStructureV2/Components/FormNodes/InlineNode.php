<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class InlineNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;

    /** @var FormNode[] */
    private array $children = [];

    public function __construct(FormNode ...$children)
    {
        $this->children = $children;
    }

    public static function make(FormNode ...$children): self
    {
        return new self(...$children);
    }

    public function addChildren(FormNode ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    /**
     * @return FormNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function childNodes(): array
    {
        return $this->children;
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context;
    }
}
