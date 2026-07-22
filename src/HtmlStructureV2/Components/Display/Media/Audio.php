<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 音频展示组件：列表内联播放器（`<audio controls>`）。
 *
 * 支持两种数据来源：
 * - PHP 数组：后端已知的一组音频地址，按顺序渲染为多个原生播放器。
 * - JS 变量字符串：如 `row.audios`，按 v-for 渲染。
 *
 * `prop` 仅对「对象数组」有意义：`[{url:'...'}]` 传 `prop('url')` 取真实地址；
 * 纯字符串数组传 `prop('')` 即可。
 */
final class Audio implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var string[]|string */
    private readonly array|string $sources;
    private bool $isStatic;
    private string $prop;
    private int $width = 320;
    private int $gap = 8;
    private ?int $limit = null;
    private string $placeholder = '-';

    /**
     * @param string[]|string $sources 音频地址数组，或 JS 变量表达式（动态）。
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

    /** 每个播放器宽度（px），影响 `<audio width>`。 */
    public function width(int $width): self
    {
        $this->width = max(1, $width);

        return $this;
    }

    /** 多个播放器之间的间距（px）。 */
    public function gap(int $gap): self
    {
        $this->gap = max(0, $gap);

        return $this;
    }

    /** 最多展示前 N 个；传 null 表示全部。 */
    public function limit(?int $limit): self
    {
        $this->limit = $limit === null ? null : max(1, $limit);

        return $this;
    }

    /**
     * 数据为空时的占位文案，默认 "-"。
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

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

    public function getGap(): int
    {
        return $this->gap;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
