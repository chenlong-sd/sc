<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\ListWidget;

final class Lists
{
    public static function make(string $key): ListWidget
    {
        return ListWidget::make($key);
    }
}
