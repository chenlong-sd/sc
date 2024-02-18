<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemTable;

/**
 * Interface FormItemSubmitThemeInterface
 */
interface FormItemTableThemeInterface
{
    public function render(FormItemTable|FormItemAttrGetter $formItemTable): AbstractHtmlElement;
}