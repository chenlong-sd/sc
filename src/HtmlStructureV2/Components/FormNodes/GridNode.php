<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class GridNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    private int $gutter = 16;

    public function __construct()
    {
    }

    /**
     * 直接创建一个栅格布局节点。
     *
     * @return self 栅格布局节点实例。
     *
     * 示例：
     * `GridNode::make()`
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 追加当前栅格布局项。
     * 推荐使用这个方法表达“这一组栅格里有哪些项”。
     *
     * @param FormNode ...$children 要追加的布局项。
     * @return self 当前栅格布局节点。
     *
     * 示例：
     * `Forms::grid()->addItems(Fields::text('title', '标题')->span(12))`
     */
    public function addItems(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 设置栅格列间距，单位与 Element Plus 的 gutter 一致。
     *
     * @param int $gutter 栅格间距。
     * @return self 当前栅格布局节点。
     *
     * 示例：
     * `Forms::grid()->gutter(24)`
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
