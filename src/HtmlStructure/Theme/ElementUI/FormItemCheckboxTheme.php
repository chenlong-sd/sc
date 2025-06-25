<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCheckbox;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCheckboxThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemCheckboxTheme extends AbstractFormItemTheme implements FormItemCheckboxThemeInterface
{
    /**
     * @param FormItemAttrGetter|FormItemCheckbox $formItem
     *
     * @return AbstractHtmlElement
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        if (!$optionsVar = $formItem->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItem->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $checkbox = El::double('el-checkbox')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            ':value'  => 'item.value',
            ':disabled' => "item.disabled"
        ])->append('{{ item.label }}');
        if (count($formItem->getOptions()) == 1 && !$formItem->getOptionsVarName()){
            $box = El::fictitious();
            $checkbox->setAttrs($formItem->getVAttrs())
                ->setAttr('v-model', $this->getVModel($formItem));
        }else{
            $box = El::double('el-checkbox-group')->setAttr('v-model', $this->getVModel($formItem))
                ->setAttrs($formItem->getVAttrs());
        }

        $checkbox->setAttrs($formItem->getOptionsAttrs());

        $this->setOptions($formItem, $optionsVar);

        $this->addEvent($box, $formItem->getEvents(), $formItem->getName());


        return $base->append($box->append($checkbox));
    }
}