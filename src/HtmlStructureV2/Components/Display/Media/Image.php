<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 单图展示组件，基于 Element Plus 的 el-image。
 *
 * 默认行为：
 * - 尺寸 90x90、fit=cover、圆角 3px；
 * - 开启点击预览（preview-src-list = [src]）；
 * - 点击遮罩关闭预览。
 */
final class Image implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $srcAttr;
    private bool $isStatic;
    private int $width = 90;
    private int $height = 90;
    private string $fit = 'cover';
    private ?int $radius = 3;
    private bool $preview = true;
    private bool $lazy = false;

    public function __construct(string $src, bool $isStatic = false)
    {
        $this->isStatic = $isStatic;
        $this->srcAttr = $isStatic ? 'src' : ':src';
        $this->attr($this->srcAttr, $src);
    }

    public static function make(string $src, bool $isStatic = false): self
    {
        return new self($src, $isStatic);
    }

    /** 缩略图宽高（px）。 */
    public function size(int $width, int $height): self
    {
        $this->width = max(1, $width);
        $this->height = max(1, $height);

        return $this;
    }

    /** 图片裁剪方式：cover / contain / fill / none / scale-down。 */
    public function fit(string $fit): self
    {
        $this->fit = $fit;

        return $this;
    }

    /** 圆角（px），传 null 取消圆角。 */
    public function radius(?int $radius): self
    {
        $this->radius = $radius;

        return $this;
    }

    /** 是否开启点击预览（默认开启）。 */
    public function preview(bool $preview = true): self
    {
        $this->preview = $preview;

        return $this;
    }

    /** 是否懒加载。 */
    public function lazy(bool $lazy = true): self
    {
        $this->lazy = $lazy;

        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getSrc(): string
    {
        return (string) ($this->renderAttributes[$this->srcAttr] ?? '');
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getFit(): string
    {
        return $this->fit;
    }

    public function getRadius(): ?int
    {
        return $this->radius;
    }

    public function isPreview(): bool
    {
        return $this->preview;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }
}
