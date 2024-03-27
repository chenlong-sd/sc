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

    public function render(FormItemCustomize|FormItemAttrGetter $formItemCustomize): AbstractHtmlElement
    {
        $element = El::get($formItemCustomize->getElement());
        if ($element instanceof TextCharacters) {
            $element = El::fromCode('<el-text style="line-height: 30px;display: inline-block;margin-bottom: 10px"></el-text>')->append($element);
        }
        if ($formItemCustomize->getWhen()){
            $element->setAttr('v-if', $formItemCustomize->getWhen());
        }
        if ($attrs = $formItemCustomize->getVAttrs()){
            if (isset($attrs['style'])) {
                $element->setAttr('style', $element->getAttr('style') . ';' . $attrs['style']);
                unset($attrs['style']);
            }
            $attrs and $element->setAttrs($attrs);
        }

        $res = El::double('el-col')->setAttr(':span', $formItemCustomize->getCol())->append($element);
        if ($formItemCustomize->getAfterCol()) {
            $res->after(El::double('el-col')->setAttr(':span', $formItemCustomize->getAfterCol()));
        }
        return $res->getParent() ?: $res;
    }
}