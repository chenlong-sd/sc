<?php

namespace Sc\Util\HtmlStructureV2\Components\Layout;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Card implements RenderableContainer, EventAware
{
    use HasElementEvents;
    use RendersWithTheme;

    /** @var Renderable[] */
    private array $children = [];
    private ?string $title = null;

    public function __construct(?string $title = null, Renderable ...$children)
    {
        $this->title = $title;
        $this->children = $children;
    }

    /**
     * 直接创建一个轻量卡片实例。
     */
    public static function make(?string $title = null, Renderable ...$children): self
    {
        return new self($title, ...$children);
    }

    /**
     * 设置卡片标题。
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 继续向卡片内容区追加子组件。
     */
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
