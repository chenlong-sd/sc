<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemTag;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTagThemeInterface;

class FormItemTagTheme extends AbstractFormItemTheme implements FormItemTagThemeInterface
{
    /**
     * @param FormItemTag|FormItemAttrGetter $formItem
     * @return AbstractHtmlElement
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $input = h('el-input-tag')
            ->setAttr('v-model', $this->getVModel($formItem))
            ->setAttr('placeholder', $formItem->getPlaceholder())
            ->setAttr('clearable')
            ->setAttrs($formItem->getVAttrs());

        foreach ($formItem->getSlots() as $name => $slot) {
            $input->append(h('template', $slot, ['#' . $name => '']));
        }
        return $this->getBaseEl($formItem)->append($input);
    }
}