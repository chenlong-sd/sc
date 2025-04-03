<?php

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCheckbox;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCheckboxThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemCheckBoxTheme extends AbstractFormItemTheme implements FormItemCheckboxThemeInterface
{

    public function render(FormItemCheckbox|FormItemAttrGetter $formItemCheckbox): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemCheckbox);
        $inputBox = El::double('div')->addClass('layui-input-block');
        if (!$filter = $formItemCheckbox->getOptionsVarName()) {
            mt_srand();
            $filter = $formItemCheckbox->getName() . 'Rand' .  mt_rand(1, 999);
        }
        $baseVar = $this->getVModel($formItemCheckbox);

        $this->defaultHandle($filter, $baseVar);
        $this->changeEvent($formItemCheckbox, $filter, $baseVar);

        $checkbox = [];
        foreach ($formItemCheckbox->getOptions() as ['value' => $value, 'label' => $label]) {
            $checkbox[] = El::double('input')->setAttrs([
                'value'      => $value,
                'title'      => $label,
                'type'       => 'checkbox',
                'lay-filter' => $filter,
                'class'      => 'layui-input'
            ]);
        }

        return $base->append($inputBox->append(...$checkbox));
    }

    private function defaultHandle($filter, $baseVar): void
    {
        Html::js()->defCodeBlock(<<<JS
            for(var i = 0; i < $baseVar.length; i++) {
                let value = {$baseVar}[i];
                layui.jquery(`input[lay-filter="$filter"][value=\${value}]`).prop('checked', true);
            }
        JS);
    }

    private function changeEvent(FormItemCheckbox|FormItemAttrGetter $formItemCheckbox, $filter, $baseVar): void
    {
        $code = JsCode::create('let value = data.value');
        if (isset($formItemCheckbox->getEvents()['change'])){
            $code->then($formItemCheckbox->getEvents()['change']->code);
        }

        Html::js()->defCodeBlock(JsFunc::call('layui.form.on', "checkbox($filter)",
            JsFunc::anonymous(['data'],
                $code->thenIf('data.elem.checked', "$baseVar.push(value)",
                    JsCode::create("let index = $baseVar.indexOf(value)")
                        ->thenIf('index !== -1', "$baseVar.splice(index, 1)")
                )
            )
        ));
    }

}