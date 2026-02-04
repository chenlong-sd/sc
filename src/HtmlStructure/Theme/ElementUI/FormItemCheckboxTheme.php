<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCheckbox;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCheckboxThemeInterface;
use Sc\Util\ScTool;

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
        $isBoolValue = false;
        if (!$optionsVar = $formItem->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItem->getName() . 'Rand' .  mt_rand(1, 999);
            $formItem->setOptionsVarName($optionsVar);
            if (count($formItem->getOptions()) == 1){
                $isBoolValue = true;
            }
        }

        $checkbox = El::double('el-checkbox')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            ':value'  => 'item.value',
            ':disabled' => "item.disabled"
        ])->append('{{ item.label }}');


        if ($isBoolValue){
            $box = $checkbox->setAttrs($formItem->getVAttrs())
                ->setAttr('v-model', $this->getVModel($formItem));
        }else{
            $box = El::double('el-checkbox-group')->setAttr('v-model', $this->getVModel($formItem))
                ->setAttrs($formItem->getVAttrs());
        }

        $checkbox->setAttrs($formItem->getOptionsAttrs());

        $this->setOptions($formItem, $optionsVar);

        $this->addEvent($box, $formItem->getEvents(), $formItem->getName(), $formItem);

        if ($isBoolValue) {
            return $base->append($box);
        }

        return $this->elGen($formItem, $base, $box, $checkbox, $optionsVar);
    }

    /**
     * @param FormItemCheckbox|FormItemAttrGetter $formItem
     * @param AbstractHtmlElement $base
     * @param DoubleLabel $box
     * @param DoubleLabel $checkbox
     * @param $optionsVar
     * @return AbstractHtmlElement
     */
    public function elGen(FormItemCheckbox|FormItemAttrGetter $formItem, AbstractHtmlElement $base, DoubleLabel $box, DoubleLabel $checkbox, $optionsVar): AbstractHtmlElement
    {
        if ($formItem->allSelect) {
            $allSelect = h("el-checkbox", [
                "v-model" => "{$formItem->getName()}checkAll",
                "v-if" => "$optionsVar.length > 0",
                ":indeterminate" => "{$formItem->getName()}isIndeterminate",
                "@change" => "{$formItem->getName()}handleCheckAllChange",
            ])->append("全选");

            Html::js()->vue->addMethod("{$formItem->getName()}handleCheckAllChange", ['type'], Js::code(
                Js::assign("this.{$this->getVModel($formItem)}", "@type ? this.$optionsVar.map(v => v.value) : []"),
                Js::assign("this.{$formItem->getName()}isIndeterminate", "@false"),
            ));

            Html::js()->vue->set("{$formItem->getName()}isIndeterminate", "@false");
            $change = $box->getAttr("@change");
            $newChange = ScTool::random("CheckBoxCM")->get();
            $box->setAttr("@change", $newChange);
            Html::js()->vue->addMethod($newChange, ['value'], Js::code(
                Js::code($change ? "{$newChange}(value)" : ""),
                Js::code("const checkedCount = value.length"),
                Js::assign("this.{$formItem->getName()}checkAll", "@checkedCount === this.$optionsVar.length"),
                Js::assign("this.{$formItem->getName()}isIndeterminate", "@checkedCount > 0 && checkedCount < this.$optionsVar.length"),
            ));

            return $base->append($allSelect)->append($box->append($checkbox));
        }

        return $base->append($box->append($checkbox));
    }
}