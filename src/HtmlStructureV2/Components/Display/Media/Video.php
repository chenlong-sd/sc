<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 单视频展示组件：缩略视频 + 蒙层播放按钮，点击后用页面级共享 el-dialog 播放（自适应尺寸）。
 */
final class Video implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $src;
    private bool $isStatic;
    private int $width = 160;
    private int $height = 90;
    private ?int $radius = 3;
    private bool $autoplay = false;
    private ?string $poster = null;

    public function __construct(string $src, bool $isStatic = false)
    {
        $this->src = $src;
        $this->isStatic = $isStatic;
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

    /** 圆角（px），传 null 取消。 */
    public function radius(?int $radius): self
    {
        $this->radius = $radius;

        return $this;
    }

    /** 缩略视频是否自动静音播放（封面效果）。 */
    public function autoplay(bool $autoplay = true): self
    {
        $this->autoplay = $autoplay;

        return $this;
    }

    /** 视频封面（海报地址）；静态或 JS 表达式由 isStatic 控制。 */
    public function poster(?string $poster): self
    {
        $this->poster = $poster;

        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getSrc(): string
    {
        return $this->src;
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

    public function isAutoplay(): bool
    {
        return $this->autoplay;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    /**
     * 生成点击播放的表达式：写到页面共享弹窗。
     * 渲染器端调用，避免每个渲染逻辑各自重复实现。
     */
    public function buildPlayExpression(): JsExpression
    {
        return JsExpression::ensure(sprintf('__SC_V2_PAGE__.playMedia(%s)', $this->getSrc()));
    }
}
