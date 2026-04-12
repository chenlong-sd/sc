<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Button implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $type = 'default';
    private string $size = 'default';
    private bool $plain = false;
    private bool $link = false;

    public function __construct(
        private readonly string $label
    ) {
    }

    /**
     * 直接创建一个轻量按钮块实例。
     */
    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * 设置按钮类型，例如 primary / success / danger。
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置按钮尺寸，例如 large / default / small。
     */
    public function size(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * 切换为朴素按钮样式。
     */
    public function plain(bool $plain = true): self
    {
        $this->plain = $plain;

        return $this;
    }

    /**
     * 切换为 link 按钮样式。
     */
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
