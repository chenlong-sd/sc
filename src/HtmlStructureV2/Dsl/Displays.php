<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Display\DescriptionItem;
use Sc\Util\HtmlStructureV2\Components\Display\Descriptions;

final class Displays
{
    /**
     * 创建一个 descriptions 详情展示块。
     */
    public static function descriptions(): Descriptions
    {
        return Descriptions::make();
    }

    /**
     * 创建一个 descriptions item，可单独设置 span/class/style 等属性后加入 descriptions。
     */
    public static function descriptionItem(string $label, mixed $value): DescriptionItem
    {
        return DescriptionItem::make($label, $value);
    }
}
