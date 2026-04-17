<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields\Concerns;

use BadMethodCallException;

/**
 * 媒体上传字段（图片 / 视频）禁用的通用 UploadField 方法。
 * 这些方法会改变媒体字段内部固化的 kind/listType/buttonText 配置，
 * 因此在窄类型上直接禁用，避免误用。
 */
trait LocksUploadMode
{
    /**
     * @internal
     * @deprecated 媒体字段已固化单/多模式，请使用对应的 image/images/video/videos 构造器
     */
    public function uploadMultiple(bool $multiple = true): static
    {
        throw $this->mediaMethodDisabled('uploadMultiple');
    }

    /**
     * @internal
     * @deprecated 媒体字段固定使用 picture-card 列表样式
     */
    public function uploadListType(string $listType): static
    {
        throw $this->mediaMethodDisabled('uploadListType');
    }

    /**
     * @internal
     * @deprecated 媒体字段使用图标触发器，无需文字按钮
     */
    public function uploadButtonText(string $buttonText): static
    {
        throw $this->mediaMethodDisabled('uploadButtonText');
    }

    /**
     * @internal
     * @deprecated 媒体字段已固化 kind，请使用对应的 image/images/video/videos 构造器
     */
    public function asImage(bool $multiple = false): static
    {
        throw $this->mediaMethodDisabled('asImage');
    }

    /**
     * @internal
     * @deprecated 媒体字段已固化 kind，请使用对应的 image/images/video/videos 构造器
     */
    public function asVideo(bool $multiple = false): static
    {
        throw $this->mediaMethodDisabled('asVideo');
    }

    private function mediaMethodDisabled(string $method): BadMethodCallException
    {
        return new BadMethodCallException(sprintf(
            '%s() is not available on %s; use the appropriate Fields::image/images/video/videos() factory instead.',
            $method,
            static::class
        ));
    }
}
