<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Fields\Concerns\LocksUploadMode;

/**
 * 视频上传字段。默认单视频；调用 multiple() 切换到多视频。
 */
final class VideoField extends UploadField
{
    use LocksUploadMode;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label);
        parent::asVideo(false);
    }

    /**
     * 切换为多视频上传模式。多视频模式下值为数组，可再链式调用 uploadLimit() 限制数量。
     *
     * @param bool $enable 是否启用多视频，默认值为 true。
     * @return static 当前视频字段实例。
     *
     * 示例：
     * `Fields::video('clips', '视频集')->multiple()->uploadLimit(5)`
     */
    public function multiple(bool $enable = true): static
    {
        parent::asVideo($enable);

        return $this;
    }
}
