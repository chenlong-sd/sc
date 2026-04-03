<?php

namespace Sc\Util\HtmlStructureV2;

use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\Support\Document;

final class RenderContext
{
    private bool $booted = false;
    private array $meta = [];

    public function __construct(
        private readonly ThemeInterface $theme,
        private readonly Document $document
    ) {
    }

    public function bootTheme(): void
    {
        if ($this->booted) {
            return;
        }

        $this->theme->boot($this);
        $this->booted = true;
    }

    public function theme(): ThemeInterface
    {
        return $this->theme;
    }

    public function document(): Document
    {
        return $this->document;
    }

    public function set(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }
}
