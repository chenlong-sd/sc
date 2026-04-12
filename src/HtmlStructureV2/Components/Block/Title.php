<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Title implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private ?string $description = null;

    public function __construct(
        private readonly string $text
    ) {
    }

    /**
     * 直接创建一个标题块实例。
     */
    public static function make(string $text): self
    {
        return new self($text);
    }

    /**
     * 设置标题下方的描述说明。
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
