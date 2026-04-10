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
        private readonly string $title
    ) {
    }

    /**
     * 直接创建一个折叠项节点。
     */
    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * 继续向当前折叠项追加子节点。
     */
    public function addNodes(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 追加当前折叠项内容。
     * 推荐使用这个方法表达“当前折叠项里放什么内容”，比通用 `addNodes()` 更直观。
     */
    public function addContent(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
    }

    /**
     * 继续向当前折叠项追加子节点。
     * `addNodes()` 的兼容别名。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
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
