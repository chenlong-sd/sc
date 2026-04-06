<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class CollapseItemNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;

    public function __construct(
        private readonly string $title,
        FormNode ...$children
    ) {
        $this->setFormNodeChildren(...$children);
    }

    /**
     * 直接创建一个折叠项节点。
     */
    public static function make(string $title, FormNode ...$children): self
    {
        return new self($title, ...$children);
    }

    /**
     * 继续向当前折叠项追加子节点。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return FormNode[]
     */
    public function getChildren(): array
    {
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context->withLabelSegment($this->title);
    }
}
