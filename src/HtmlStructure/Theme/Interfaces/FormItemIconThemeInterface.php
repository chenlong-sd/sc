<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemIcon;

/**
 * Interface FormItemIconThemeInterface
 */
interface FormItemIconThemeInterface
{
    public function render(FormItemIcon|FormItemAttrGetter $formItemIcon):AbstractHtmlElement;
}