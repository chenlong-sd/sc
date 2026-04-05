<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Grid implements RenderableContainer, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $items = [];
    private int $columns = 2;
    private string $gap = '16px';

    public function __construct(Renderable ...$items)
    {
        $this->items = $items;
    }

    public static function make(Renderable ...$items): self
    {
        return new self(...$items);
    }

    public function items(Renderable ...$items): self
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

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
    public function getItems(): array
    {
        return $this->items;
    }

    public function renderChildren(): array
    {
        return $this->items;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function getGap(): string
    {
        return $this->gap;
    }
}
