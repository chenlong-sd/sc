<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;

final class CustomNode implements FormNode
{
    use HasSpan;

    public function __construct(
        private readonly string|AbstractHtmlElement $content
    ) {
    }

    public static function make(string|AbstractHtmlElement $content): self
    {
        return new self($content);
    }

    public function content(): string|AbstractHtmlElement
    {
        return $this->content;
    }
}
