<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Text implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $type = 'default';

    public function __construct(
        private readonly string $content
    ) {
    }

    /**
     * 直接创建一个文本块实例。
     */
    public static function make(string $content): self
    {
        return new self($content);
    }

    /**
     * 设置文本样式类型，例如 muted。
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
