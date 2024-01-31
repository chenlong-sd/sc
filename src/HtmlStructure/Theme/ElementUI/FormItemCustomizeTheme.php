<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCustomize;
use Sc\Util\HtmlStructure\Form\FormItemDatetime;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCustomizeThemeInterface;

/**
 * Class FormItemCustomizeTheme
 */
class FormItemCustomizeTheme extends AbstractFormItemTheme implements FormItemCustomizeThemeInterface
{

    public function render(FormItemCustomize|FormItemAttrGetter $formItemDatetime): AbstractHtmlElement
    {
        $element = El::get($formItemDatetime->getElement());
        if ($element instanceof TextCharacters) {
            $element = El::fromCode('<el-text style="line-height: 30px;display: inline-block;margin-bottom: 10px"></el-text>')->append($element);
        }
        if ($formItemDatetime->getWhen()){
            $element->setAttr('v-if', $formItemDatetime->getWhen());
        }

        $res = El::double('el-col')->setAttr(':span', $formItemDatetime->getCol())->append($element);
        if ($formItemDatetime->getAfterCol()) {
            $res->after(El::double('el-col')->setAttr(':span', $formItemDatetime->getAfterCol()));
        }
        return $res->getParent() ?: $res;
    }
}