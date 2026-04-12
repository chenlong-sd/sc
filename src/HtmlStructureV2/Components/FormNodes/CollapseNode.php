<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class CollapseNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    private bool $accordion = false;

    public function __construct()
    {
    }

    /**
     * 直接创建一个折叠面板节点。
     *
     * @return self 折叠面板实例。
     *
     * 示例：
     * `CollapseNode::make()`
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 继续向当前折叠容器追加折叠项。
     *
     * @param CollapseItemNode ...$items 要追加的折叠项。
     * @return self 当前折叠容器。
     */
    public function addNodes(CollapseItemNode ...$items): self
    {
        return $this->appendFormNodeChildren(...$items);
    }

    /**
     * 继续向当前折叠容器追加折叠项。
     *
     * @param CollapseItemNode ...$items 要追加的折叠项。
     * @return self 当前折叠容器。
     *
     * 示例：
     * `Forms::collapse()->addItems(Forms::collapseItem('高级设置'))`
     */
    public function addItems(CollapseItemNode ...$items): self
    {
        return $this->addNodes(...$items);
    }

    /**
     * 开启手风琴模式，同一时间只展开一个折叠项。
     *
     * @param bool $accordion 是否开启手风琴模式，默认值为 true。
     * @return self 当前折叠容器。
     *
     * 示例：
     * `Forms::collapse()->accordion()`
     */
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
