<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Text implements Renderable
{
    use RendersWithTheme;

    private string $type = 'default';

    public function __construct(
        private readonly string $content
    ) {
    }

    public static function make(string $content): self
    {
        return new self($content);
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
