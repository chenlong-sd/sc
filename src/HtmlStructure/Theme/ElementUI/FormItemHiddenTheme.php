<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemHidden;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemHiddenThemeInterface;

class FormItemHiddenTheme extends AbstractFormItemTheme implements FormItemHiddenThemeInterface
{
    /**
     * @param FormItemHidden|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        return El::fictitious();
    }
}