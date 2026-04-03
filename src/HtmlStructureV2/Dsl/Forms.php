<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Form;

final class Forms
{
    public static function make(string $key): Form
    {
        return Form::make($key);
    }
}
