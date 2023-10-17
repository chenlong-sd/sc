<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemGroup;

/**
 * Interface FormItemSubmitThemeInterface
 */
interface FormItemGroupThemeInterface
{
    public function render(FormItemGroup|FormItemAttrGetter $formItemGroup): AbstractHtmlElement;
}