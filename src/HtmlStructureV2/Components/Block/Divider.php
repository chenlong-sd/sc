<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Divider implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    public function __construct(
        private readonly ?string $text = null
    ) {
    }

    /**
     * 直接创建一个分割线块实例。
     */
    public static function make(?string $text = null): self
    {
        return new self($text);
    }

    public function text(): ?string
    {
        return $this->text;
    }
}
