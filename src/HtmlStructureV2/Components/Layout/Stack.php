<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Stack implements RenderableContainer, EventAware
{
    use HasElementEvents;
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $children = [];
    private string $gap = '16px';

    public function __construct(Renderable ...$children)
    {
        $this->children = $children;
    }

    /**
     * 直接创建一个纵向堆叠布局实例。
     */
    public static function make(Renderable ...$children): self
    {
        return new self(...$children);
    }

    /**
     * 继续向纵向堆叠布局追加子组件。
     */
    public function children(Renderable ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    /**
     * 设置子组件之间的纵向间距，例如 12px / 1rem。
     */
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
