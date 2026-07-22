<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 多视频展示：一组缩略视频，点击任一个均触发共享 el-dialog 播放。
 *
 * 数据来源同 Images：
 * - PHP 数组：每段是视频地址，或 ['url'=>'...'] 的对象；
 * - JS 变量字符串：如 `row.videos`。`prop` 决定真实地址字段，空串表示元素自身即地址。
 */
final class Videos implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var string[]|string */
    private readonly array|string $sources;
    private bool $isStatic;
    private string $prop;
    private int $width = 160;
    private int $height = 90;
    private ?int $radius = 3;
    private int $gap = 8;
    private ?int $limit = null;
    private bool $autoplay = false;
    private string $placeholder = '-';

    /**
     * @param string[]|string $sources
     */
    public function __construct(array|string $sources, string $prop = 'url')
    {
        $this->sources = $sources;
        $this->isStatic = is_array($sources);
        $this->prop = $prop;
    }

    public static function make(array|string $sources, string $prop = 'url'): self
    {
        return new self($sources, $prop);
    }

    public function size(int $width, int $height): self
    {
        $this->width = max(1, $width);
        $this->height = max(1, $height);

        return $this;
    }

    public function radius(?int $radius): self
    {
        $this->radius = $radius;

        return $this;
    }

    public function gap(int $gap): self
    {
        $this->gap = max(0, $gap);

        return $this;
    }

    public function limit(?int $limit): self
    {
        $this->limit = $limit === null ? null : max(1, $limit);

        return $this;
    }

    /** 缩略视频是否自动静音播放（封面效果）。 */
    public function autoplay(bool $autoplay = true): self
    {
        $this->autoplay = $autoplay;

        return $this;
    }

    /** @return string[]|string */
    public function getSources(): array|string
    {
        return $this->sources;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getProp(): string
    {
        return $this->prop;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getRadius(): ?int
    {
        return $this->radius;
    }

    public function getGap(): int
    {
        return $this->gap;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function isAutoplay(): bool
    {
        return $this->autoplay;
    }

    /**
     * 数据为空时的占位文案，默认 "-"。
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
