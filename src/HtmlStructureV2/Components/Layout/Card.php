<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Card implements RenderableContainer, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $children = [];
    private ?string $title = null;

    public function __construct(?string $title = null, Renderable ...$children)
    {
        $this->title = $title;
        $this->children = $children;
    }

    public static function make(?string $title = null, Renderable ...$children): self
    {
        return new self($title, ...$children);
    }

    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function children(Renderable ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
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
}
