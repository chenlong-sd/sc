<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCustomize;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCustomizeThemeInterface;

/**
 * Class FormItemCustomizeTheme
 */
class FormItemCustomizeTheme extends AbstractFormItemTheme implements FormItemCustomizeThemeInterface
{
    /**
     * @param FormItemAttrGetter|FormItemCustomize $formItem
     *
     * @return AbstractHtmlElement
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $element = El::get($formItem->getElement());
        if ($element instanceof TextCharacters) {
            $element = $formItem->getIsGroupTitle()
                ? h('div', $element)->setStyle("{
                    padding-bottom: 3px;
                    font-weight: bold;
                    font-size: 18px;
                    border-bottom: 2px solid #808080a3;
                    margin-bottom: 20px;
                    color: #303133;
                    margin-top: 10px;
                }")
                : El::fromCode('<el-text style="line-height: 30px;display: inline-block;margin-bottom: 10px;"></el-text>')->append($element);
        }
        if ($formItem->getLabel() !== null) {
            $element = $this->getBaseEl($formItem)->append($element);
        }

        if ($formItem->getWhen()){
            $element->setAttr('v-if', $formItem->getWhen());
        }
        if ($attrs = $formItem->getVAttrs()){
            if (isset($attrs['style'])) {
                $element->setAttr('style', $element->getAttr('style') . ';' . $attrs['style']);
                unset($attrs['style']);
            }
            $attrs and $element->setAttrs($attrs);
        }

        return $element;
    }
}