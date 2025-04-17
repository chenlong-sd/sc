<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInLine;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;

/**
 * Interface FormItemSubmitThemeInterface
 */
interface FormItemInLineThemeInterface
{
    public function render(FormItemInLine|FormItemAttrGetter $formItemInLine): AbstractHtmlElement;
}