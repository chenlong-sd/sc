<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 多图展示组件，基于 Element Plus 的 el-image 列表 + 统一预览组。
 *
 * 支持两种数据来源：
 * - PHP 数组：后端已知的一组图片地址，按顺序渲染。
 * - JS 变量字符串：如 `row.images`，按 v-for 渲染。
 *
 * `prop` 仅对「对象数组」有意义：`[{url:'...'}]` 传 `prop('url')` 取真实地址；
 * 纯字符串数组传 `prop('')` 即可。
 */
final class Images implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var string[]|string */
    private readonly array|string $sources;
    private bool $isStatic;
    private string $prop;
    private int $width = 90;
    private int $height = 90;
    private string $fit = 'cover';
    private ?int $radius = 3;
    private bool $preview = true;
    private bool $lazy = false;
    private int $gap = 8;
    private ?int $limit = null;
    private string $placeholder = '-';

    /**
     * @param string[]|string $sources 图片地址数组，或 JS 变量表达式（动态）。
     * @param string $prop 当元素是对象时取真实地址的字段名；空字符串表示元素本身就是地址。
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

    /** 单图缩略图宽高（px）。 */
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

    /** 是否开启点击预览（默认开启），所有图共享同一组预览列表。 */
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

    /** 多图之间的间距（px）。 */
    public function gap(int $gap): self
    {
        $this->gap = max(0, $gap);

        return $this;
    }

    /** 最多展示前 N 张（其余仍计入预览组）；传 null 表示全部展示。 */
    public function limit(?int $limit): self
    {
        $this->limit = $limit === null ? null : max(1, $limit);

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

    public function getGap(): int
    {
        return $this->gap;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
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
