<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Display\Descriptions;

final class Displays
{
    public static function descriptions(): Descriptions
    {
        return Descriptions::make();
    }
}
