<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class TabPaneNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;
    private bool $lazy = false;

    public function __construct(
        private readonly string $label
    ) {
    }

    /**
     * 直接创建一个标签页面板节点。
     */
    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * 继续向当前 tab 面板追加子节点。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 仅在标签被激活时再渲染面板内容。
     */
    public function lazy(bool $lazy = true): self
    {
        $this->lazy = $lazy;

        return $this;
    }

    public function label(): string
    {
        return $this->label;
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
        return $context->withLabelSegment($this->label);
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }
}
