<?php
/**
 * datetime: 2023/6/3 23:15
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCheckbox;
use Sc\Util\HtmlStructure\Form\FormItemRadio;
use Sc\Util\HtmlStructure\Form\FormItemSelect;

interface FormItemRadioThemeInterface
{
    public function render(FormItemRadio|FormItemAttrGetter $formItemRadio): AbstractHtmlElement;
}