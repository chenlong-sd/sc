<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Grid implements RenderableContainer, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $items = [];
    private int $columns = 2;
    private string $gap = '16px';

    public function __construct(Renderable ...$items)
    {
        $this->items = $items;
    }

    /**
     * 直接创建一个轻量网格布局实例。
     */
    public static function make(Renderable ...$items): self
    {
        return new self(...$items);
    }

    /**
     * 继续向网格布局追加子组件。
     */
    public function items(Renderable ...$items): self
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * 设置网格列数。
     */
    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    /**
     * 设置网格间距，例如 16px / 1rem。
     */
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
