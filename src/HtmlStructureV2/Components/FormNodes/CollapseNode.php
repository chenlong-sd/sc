<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class CollapseNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;
    private bool $accordion = false;

    public function __construct(CollapseItemNode ...$items)
    {
        $this->setFormNodeChildren(...$items);
    }

    public static function make(CollapseItemNode ...$items): self
    {
        return new self(...$items);
    }

    public function addItems(CollapseItemNode ...$items): self
    {
        return $this->appendFormNodeChildren(...$items);
    }

    public function accordion(bool $accordion = true): self
    {
        $this->accordion = $accordion;

        return $this;
    }

    /**
     * @return CollapseItemNode[]
     */
    public function getItems(): array
    {
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context;
    }

    public function isAccordion(): bool
    {
        return $this->accordion;
    }
}
