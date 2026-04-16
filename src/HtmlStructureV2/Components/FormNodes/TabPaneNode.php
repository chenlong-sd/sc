<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasReadonly;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class TabPaneNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    use HasReadonly;
    private bool $lazy = false;

    public function __construct(
        private readonly string $label
    ) {
    }

    /**
     * 直接创建一个标签页面板节点。
     *
     * @param string $label 标签名称。
     * @return self 标签面板实例。
     *
     * 示例：
     * `TabPaneNode::make('基础信息')`
     */
    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * 追加当前 tab 面板内容。
     * 推荐使用这个方法表达“当前页签里放什么内容”。
     *
     * @param FormNode ...$children 要追加的内容节点。
     * @return self 当前标签面板。
     *
     * 示例：
     * `Forms::tab('基础信息')->addContent(Fields::text('title', '标题'))`
     */
    public function addContent(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 仅在标签被激活时再渲染面板内容。
     *
     * @param bool $lazy 是否懒渲染，默认值为 true。
     * @return self 当前标签面板。
     *
     * 示例：
     * `Forms::tab('高级设置')->lazy()`
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
        return $context
            ->withLabelSegment($this->label)
            ->mergeReadonly($this->isReadonly());
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }
}
