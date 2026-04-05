<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Title implements Renderable, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    private ?string $description = null;

    public function __construct(
        private readonly string $text
    ) {
    }

    public static function make(string $text): self
    {
        return new self($text);
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
