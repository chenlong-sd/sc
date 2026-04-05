<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Alert implements Renderable
{
    use RendersWithTheme;

    private string $type = 'info';

    public function __construct(
        private readonly string $title,
        private readonly ?string $description = null
    ) {
    }

    public static function make(string $title, ?string $description = null): self
    {
        return new self($title, $description);
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
