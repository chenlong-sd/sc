<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCheckbox;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCheckboxThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemCheckboxTheme extends AbstractFormItemTheme implements FormItemCheckboxThemeInterface
{

    public function render(FormItemCheckbox|FormItemAttrGetter $formItemCheckbox): AbstractHtmlElement
    {
        $formItemCheckbox->getDefault() or $formItemCheckbox->default([]);

        $base = $this->getBaseEl($formItemCheckbox);

        if (!$optionsVar = $formItemCheckbox->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItemCheckbox->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $box = El::double('el-checkbox-group')->setAttr('v-model', $this->getVModel($formItemCheckbox))
            ->setAttrs($formItemCheckbox->getVAttrs());
        $checkbox = El::double('el-checkbox')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            ':label'  => 'item.value'
        ])->append('{{ item.label }}');

        $this->addEvent($box, $formItemCheckbox->getEvents(), $formItemCheckbox->getName());

        $this->setOptions($formItemCheckbox, $optionsVar);

        return $this->afterRender($formItemCheckbox, $base->append($box->append($checkbox)));
    }
}