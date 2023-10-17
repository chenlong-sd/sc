<?php

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;

/**
 * Class AbstractFormItemTheme
 */
abstract class AbstractFormItemTheme
{
    public function getBaseEl(FormItemInterface|FormItemAttrGetter $formItem): AbstractHtmlElement
    {
        $el = El::double('div')->addClass('layui-form-item');

        if ($formItem->getLabel()) {
            $labelEl = El::double('div')->addClass('layui-form-label')->append($formItem->getLabel());

            $el->append($labelEl);
        }

        return $el;
    }


    public function getVModel(FormItemInterface|FormItemAttrGetter $formItem): ?string
    {
        return $formItem->getName() ? implode('.', array_filter([$formItem->getFormModel(), $formItem->getName()])) : null;
    }
}