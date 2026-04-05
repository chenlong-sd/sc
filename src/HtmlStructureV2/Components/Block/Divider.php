<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Divider implements Renderable
{
    use RendersWithTheme;

    public function __construct(
        private readonly ?string $text = null
    ) {
    }

    public static function make(?string $text = null): self
    {
        return new self($text);
    }

    public function text(): ?string
    {
        return $this->text;
    }
}
