<?php

namespace Sc\Util\HtmlStructureV2\Components\Block;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Alert implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $type = 'info';

    public function __construct(
        private readonly string $title,
        private readonly ?string $description = null
    ) {
    }

    /**
     * 直接创建一个提示块实例。
     */
    public static function make(string $title, ?string $description = null): self
    {
        return new self($title, $description);
    }

    /**
     * 设置提示块类型，例如 info / success / warning / error。
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
