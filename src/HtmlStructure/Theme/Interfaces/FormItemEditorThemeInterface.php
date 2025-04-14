<?php
/**
 * datetime: 2023/6/7 23:21
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemEditor;

interface FormItemEditorThemeInterface
{
    public function render(FormItemEditor|FormItemAttrGetter $formItemEditor): AbstractHtmlElement;
}