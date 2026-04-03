<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class Dialogs
{
    public static function make(string $key, string $title): Dialog
    {
        return Dialog::make($key, $title);
    }
}
