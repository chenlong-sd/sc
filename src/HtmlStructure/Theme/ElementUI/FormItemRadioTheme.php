<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemRadio;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemRadioThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemRadioTheme extends AbstractFormItemTheme implements FormItemRadioThemeInterface
{
    /**
     * @param FormItemAttrGetter|FormItemRadio $formItem
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

        $checkbox = El::double('el-radio')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            'v-model' => $this->getVModel($formItem),
            ':value'  => 'item.value',
            ':disabled' => "item.disabled",
        ])->append('{{ item.label }}')
            ->setAttrs($formItem->getVAttrs())
            ->setAttrs($formItem->getOptionsAttrs());

        $this->setOptions($formItem, $optionsVar);

        $this->addEvent($checkbox, $formItem->getEvents(), $formItem->getName());

        return $base->append($checkbox);
    }
}