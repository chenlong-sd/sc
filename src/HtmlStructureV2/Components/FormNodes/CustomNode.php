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

    public static function make(string|AbstractHtmlElement|Renderable $content): self
    {
        return new self($content);
    }

    public function content(): string|AbstractHtmlElement|Renderable
    {
        return $this->content;
    }

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
