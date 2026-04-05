<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Stack implements RenderableContainer
{
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $children = [];
    private string $gap = '16px';

    public function __construct(Renderable ...$children)
    {
        $this->children = $children;
    }

    public static function make(Renderable ...$children): self
    {
        return new self(...$children);
    }

    public function children(Renderable ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    public function gap(string $gap): self
    {
        $this->gap = $gap;

        return $this;
    }

    /**
     * @return Renderable[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function renderChildren(): array
    {
        return $this->children;
    }

    public function getGap(): string
    {
        return $this->gap;
    }
}
