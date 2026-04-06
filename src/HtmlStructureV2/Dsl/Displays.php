<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

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
}
