<?php
/**
 * datetime: 2023/6/3 23:15
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSwitch;

interface FormItemSwitchThemeInterface
{
    public function render(FormItemSwitch|FormItemAttrGetter $formItemSwitch): AbstractHtmlElement;
}