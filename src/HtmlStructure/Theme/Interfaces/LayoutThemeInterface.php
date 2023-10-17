<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Layout;

/**
 * Interface LayoutThemeInterface
 */
interface LayoutThemeInterface
{
    public function render(Layout $layout): AbstractHtmlElement;
}