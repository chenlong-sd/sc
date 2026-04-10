<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class InlineNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;

    public function __construct()
    {
    }

    /**
     * 直接创建一个行内布局节点。
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 继续向当前行内容器追加子节点。
     */
    public function addNodes(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 追加当前行内布局项。
     * 推荐使用这个方法表达“这一行里有哪些项”，比通用 `addNodes()` 更符合布局语义。
     */
    public function addItems(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
    }

    /**
     * 继续向当前行内容器追加子节点。
     * `addNodes()` 的兼容别名。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
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
        return $context;
    }
}
