<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class GridNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;
    private int $gutter = 16;

    public function __construct()
    {
    }

    /**
     * 直接创建一个栅格布局节点。
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 继续向当前栅格容器追加子节点。
     */
    public function addNodes(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 追加当前栅格布局项。
     * 推荐使用这个方法表达“这一组栅格里有哪些项”，比通用 `addNodes()` 更符合布局语义。
     */
    public function addItems(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
    }

    /**
     * 继续向当前栅格容器追加子节点。
     * `addNodes()` 的兼容别名。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->addNodes(...$children);
    }

    /**
     * 设置栅格列间距，单位与 Element Plus 的 gutter 一致。
     */
    public function gutter(int $gutter): self
    {
        $this->gutter = max(0, $gutter);

        return $this;
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

    public function getGutter(): int
    {
        return $this->gutter;
    }
}
