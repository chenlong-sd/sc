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
