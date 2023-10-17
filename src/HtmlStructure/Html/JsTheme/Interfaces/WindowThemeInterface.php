<?php
/**
 * datetime: 2023/6/1 23:30
 **/

namespace Sc\Util\HtmlStructure\Html\JsTheme\Interfaces;

use Sc\Util\HtmlStructure\Html\Js\Window;

interface WindowThemeInterface
{
    public function render(Window $window): string;
}