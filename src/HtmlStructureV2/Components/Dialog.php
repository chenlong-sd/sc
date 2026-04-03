<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Dialog implements Renderable
{
    use RendersWithTheme;

    private string $width = '760px';
    private array $footerActions = [];
    private ?Form $form = null;

    public function __construct(
        private readonly string $key,
        private readonly string $title
    ) {
    }

    public static function make(string $key, string $title): self
    {
        return new self($key, $title);
    }

    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function form(Form $form): self
    {
        $this->form = $form;

        return $this;
    }

    public function footer(Action ...$actions): self
    {
        $this->footerActions = array_merge($this->footerActions, $actions);

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function getFooterActions(): array
    {
        return $this->footerActions;
    }
}
