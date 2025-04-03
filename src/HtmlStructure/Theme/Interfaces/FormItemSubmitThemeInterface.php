<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;

/**
 * Interface FormItemSubmitThemeInterface
 */
interface FormItemSubmitThemeInterface
{
    public function render(FormItemSubmit|FormItemAttrGetter $formItemSubmit): AbstractHtmlElement;
}