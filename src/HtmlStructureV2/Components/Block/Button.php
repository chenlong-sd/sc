<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Button implements Renderable, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    private string $type = 'default';
    private string $size = 'default';
    private bool $plain = false;
    private bool $link = false;

    public function __construct(
        private readonly string $label
    ) {
    }

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function size(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function plain(bool $plain = true): self
    {
        $this->plain = $plain;

        return $this;
    }

    public function link(bool $link = true): self
    {
        $this->link = $link;

        return $this;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function buttonType(): string
    {
        return $this->type;
    }

    public function buttonSize(): string
    {
        return $this->size;
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }

    public function isLink(): bool
    {
        return $this->link;
    }
}
