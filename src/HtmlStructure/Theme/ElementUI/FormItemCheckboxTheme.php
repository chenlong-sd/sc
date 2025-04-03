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
        $formItem->getDefault() or $formItem->default([]);

        $base = $this->getBaseEl($formItem);

        if (!$optionsVar = $formItem->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItem->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $box = El::double('el-checkbox-group')->setAttr('v-model', $this->getVModel($formItem))
            ->setAttrs($formItem->getVAttrs());
        $checkbox = El::double('el-checkbox')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            ':label'  => 'item.value',
            ':disabled' => "item.disabled"
        ])->append('{{ item.label }}');

        $checkbox->setAttrs($formItem->getOptionsAttrs());

        $this->setOptions($formItem, $optionsVar);

        $this->addEvent($box, $formItem->getEvents(), $formItem->getName());


        return $base->append($box->append($checkbox));
    }
}