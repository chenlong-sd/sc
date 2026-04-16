<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeLabelWidth;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasReadonly;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class CollapseItemNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    use HasReadonly;
    use HasFormNodeLabelWidth;

    public function __construct(
        private readonly string $title
    ) {
    }

    /**
     * 直接创建一个折叠项节点。
     *
     * @param string $title 折叠项标题。
     * @return self 折叠项实例。
     *
     * 示例：
     * `CollapseItemNode::make('高级设置')`
     */
    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * 追加当前折叠项内容。
     * 推荐使用这个方法表达“当前折叠项里放什么内容”。
     *
     * @param FormNode ...$children 要追加的内容节点。
     * @return self 当前折叠项。
     *
     * 示例：
     * `Forms::collapseItem('高级设置')->addContent(Fields::text('remark', '备注'))`
     */
    public function addContent(FormNode ...$children): self
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
        return $context
            ->withLabelSegment($this->title)
            ->mergeReadonly($this->isReadonly());
    }
}
