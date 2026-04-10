<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class CustomNode implements FormNode
{
    use HasSpan;

    private ?string $columnLabel = null;

    public function __construct(
        private readonly string|AbstractHtmlElement|Renderable $content
    ) {
    }

    /**
     * 直接创建一个自定义内容节点。
     * 若传入 Renderable，当前仅支持轻量 blocks/layouts/displays；
     * 其中事件型轻组件在表单场景下通常还能拿到 `model` 作为运行时 context。
     *
     * @param string|AbstractHtmlElement|Renderable $content 自定义内容。
     * @return self 自定义节点实例。
     *
     * 示例：
     * `CustomNode::make('<div class="help-text">请先填写基础信息</div>')`
     */
    public static function make(string|AbstractHtmlElement|Renderable $content): self
    {
        return new self($content);
    }

    public function content(): string|AbstractHtmlElement|Renderable
    {
        return $this->content;
    }

    /**
     * 在表格场景中为当前自定义节点指定列标题。
     * 主要用于 Forms::table() 中的自定义列头文案。
     *
     * @param string|null $label 列标题。
     * @return self 当前自定义节点。
     *
     * 示例：
     * `Forms::custom('操作说明')->columnLabel('说明')`
     */
    public function columnLabel(?string $label): self
    {
        $this->columnLabel = $label === null ? null : trim($label);

        return $this;
    }

    public function getColumnLabel(): ?string
    {
        return $this->columnLabel;
    }
}
