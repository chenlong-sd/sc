<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Fields\Concerns\LocksUploadMode;

/**
 * 图片上传字段。默认单图；调用 multiple() 切换到多图。
 */
final class ImageField extends UploadField
{
    use LocksUploadMode;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label);
        parent::asImage(false);
    }

    /**
     * 切换为多图上传模式。多图模式下值为数组，可再链式调用 uploadLimit() 限制数量。
     *
     * @param bool $enable 是否启用多图，默认值为 true。
     * @return static 当前图片字段实例。
     *
     * 示例：
     * `Fields::image('gallery', '相册')->multiple()->uploadLimit(9)`
     */
    public function multiple(bool $enable = true): static
    {
        parent::asImage($enable);

        return $this;
    }
}
