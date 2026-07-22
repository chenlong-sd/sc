<?php

namespace Sc\Util\HtmlStructureV2\Components\Display\Media;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 单个附件展示，基于 el-link + 图标 + 可选文件大小与下载。
 */
final class File implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private string $urlAttr;
    private ?string $name;
    private bool $isStatic;
    private ?string $icon = null;
    private ?string $size = null;
    private bool $download = false;
    private string $target = '_blank';
    private string $linkType = 'primary';

    public function __construct(string $name, string $url, bool $isStatic = false)
    {
        $this->name = $name;
        $this->isStatic = $isStatic;
        $this->urlAttr = $isStatic ? 'href' : ':href';
        $this->attr($this->urlAttr, $url);
    }

    public static function make(string $name, string $url, bool $isStatic = false): self
    {
        return new self($name, $url, $isStatic);
    }

    /** 文件名；可传 JS 表达式（变量模式时配合 isStatic=false）。 */
    public function name(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** 引导图标名（Element Plus 图标组件名），传 null 关闭图标。 */
    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /** 展示的文件大小文案，如 "1.2 MB"；传 null 不展示。 */
    public function sizeLabel(?string $size): self
    {
        $this->size = $size;

        return $this;
    }

    /** 是否启用 download 属性（浏览器直接下载）。 */
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

    /** el-link 的 type：primary / success / warning / danger / info / default。 */
    public function type(string $linkType): self
    {
        $this->linkType = $linkType;

        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return (string) ($this->renderAttributes[$this->urlAttr] ?? '');
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getSizeLabel(): ?string
    {
        return $this->size;
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

    /** 自动按扩展名推断 Element Plus 图标组件名。 */
    public static function guessIconFromName(string $name): string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return match ($ext) {
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp' => 'Picture',
            'mp4', 'mov', 'avi', 'mkv', 'webm' => 'VideoPlay',
            'mp3', 'wav', 'flac', 'aac' => 'Headset',
            'pdf' => 'Document',
            'zip', 'rar', '7z', 'tar', 'gz' => 'Files',
            'doc', 'docx' => 'EditPen',
            'xls', 'xlsx' => 'Grid',
            'ppt', 'pptx' => 'Tickets',
            default => 'Link',
        };
    }
}
