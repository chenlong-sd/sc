<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 多个附件列表展示。
 *
 * 支持两种数据来源（与 Images 一致）：
 * - PHP 数组：每个元素形如 ['name' => ..., 'url' => ..., 'size' => ...]。
 *   若元素是字符串，视为只有 url，名称从 url 推导。
 * - JS 变量字符串：如 `row.attachments`，按 v-for 渲染。
 *
 * `urlProp` 指定真实下载地址字段，`nameProp`/`sizeProp` 同理。
 */
final class Files implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var array<int, mixed>|string */
    private readonly array|string $sources;
    private bool $isStatic;
    private string $urlProp;
    private string $nameProp;
    private string $sizeProp;
    private bool $download = false;
    private string $target = '_blank';
    private string $linkType = 'primary';
    private bool $autoIcon = true;
    private ?string $defaultIcon = 'Link';
    private int $gap = 8;
    private string $layout = 'stack'; // stack | inline
    private string $placeholder = '-';

    /**
     * @param array<int, mixed>|string $sources
     */
    public function __construct(array|string $sources, string $urlProp = 'url', string $nameProp = 'name', string $sizeProp = 'size')
    {
        $this->sources = $sources;
        $this->isStatic = is_array($sources);
        $this->urlProp = $urlProp;
        $this->nameProp = $nameProp;
        $this->sizeProp = $sizeProp;
    }

    public static function make(array|string $sources, string $urlProp = 'url', string $nameProp = 'name', string $sizeProp = 'size'): self
    {
        return new self($sources, $urlProp, $nameProp, $sizeProp);
    }

    /** 是否启用 download 属性（所有项统一）。 */
    public function download(bool $download = true): self
    {
        $this->download = $download;

        return $this;
    }

    /** 链接 target，默认 _blank。 */
    public function target(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    /** el-link 的 type。 */
    public function type(string $linkType): self
    {
        $this->linkType = $linkType;

        return $this;
    }

    /** 是否按文件名自动推断图标（默认开启）。 */
    public function autoIcon(bool $autoIcon): self
    {
        $this->autoIcon = $autoIcon;

        return $this;
    }

    /** autoIcon 未命中时的兜底图标组件名；传 null 关闭图标。 */
    public function defaultIcon(?string $defaultIcon): self
    {
        $this->defaultIcon = $defaultIcon;

        return $this;
    }

    /** 项之间的间距（px）。 */
    public function gap(int $gap): self
    {
        $this->gap = max(0, $gap);

        return $this;
    }

    /** 排列方式：stack（竖排）或 inline（横排折行）。 */
    public function layout(string $layout): self
    {
        $this->layout = $layout === 'inline' ? 'inline' : 'stack';

        return $this;
    }

    /** @return array<int, mixed>|string */
    public function getSources(): array|string
    {
        return $this->sources;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getUrlProp(): string
    {
        return $this->urlProp;
    }

    public function getNameProp(): string
    {
        return $this->nameProp;
    }

    public function getSizeProp(): string
    {
        return $this->sizeProp;
    }

    public function isDownload(): bool
    {
        return $this->download;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function isAutoIcon(): bool
    {
        return $this->autoIcon;
    }

    public function getDefaultIcon(): ?string
    {
        return $this->defaultIcon;
    }

    public function getGap(): int
    {
        return $this->gap;
    }

    public function getLayout(): string
    {
        return $this->layout;
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
