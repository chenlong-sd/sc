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

final class TabsNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    use HasReadonly;
    use HasFormNodeLabelWidth;
    private string $type = '';
    private string $tabPosition = 'top';
    private bool $stretch = false;

    public function __construct()
    {
    }

    /**
     * 直接创建一个标签页布局节点。
     *
     * @return self 标签页容器实例。
     *
     * 示例：
     * `TabsNode::make()`
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 继续向当前标签页容器追加 tab。
     *
     * @param TabPaneNode ...$tabs 要追加的标签页。
     * @return self 当前标签页容器。
     *
     * 示例：
     * `Forms::tabs()->addTabs(Forms::tab('基础信息'))`
     */
    public function addTabs(TabPaneNode ...$tabs): self
    {
        return $this->appendFormNodeChildren(...$tabs);
    }

    /**
     * 设置标签页样式类型，例如 border-card / card。
     *
     * @param string $type 标签页类型。
     * @return self 当前标签页容器。
     *
     * 示例：
     * `Forms::tabs()->type('border-card')`
     */
    public function type(string $type): self
    {
        $this->type = trim($type);

        return $this;
    }

    /**
     * 设置标签位置，例如 top / right / bottom / left。
     *
     * @param string $tabPosition 标签位置。
     * @return self 当前标签页容器。
     *
     * 示例：
     * `Forms::tabs()->tabPosition('left')`
     */
    public function tabPosition(string $tabPosition): self
    {
        $tabPosition = trim($tabPosition);
        $this->tabPosition = $tabPosition === '' ? 'top' : $tabPosition;

        return $this;
    }

    /**
     * 让各个标签平均拉伸占满整行。
     *
     * @param bool $stretch 是否拉伸，默认值为 true。
     * @return self 当前标签页容器。
     *
     * 示例：
     * `Forms::tabs()->stretch()`
     */
    public function stretch(bool $stretch = true): self
    {
        $this->stretch = $stretch;

        return $this;
    }

    /**
     * @return TabPaneNode[]
     */
    public function getTabs(): array
    {
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context->mergeReadonly($this->isReadonly());
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTabPosition(): string
    {
        return $this->tabPosition;
    }

    public function isStretch(): bool
    {
        return $this->stretch;
    }
}
