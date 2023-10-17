<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSwitch;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSwitchThemeInterface;

class FormItemSwitchTheme extends AbstractFormItemTheme implements FormItemSwitchThemeInterface
{
    /**
     * @param FormItemSwitch|FormItemAttrGetter $formItemSwitch
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemSwitch|FormItemAttrGetter $formItemSwitch): AbstractHtmlElement
    {
        mt_srand();
        $filter   = $formItemSwitch->getName() . 'Rand' . mt_rand(1, 999);
        $base     = $this->getBaseEl($formItemSwitch);
        $inputBox = El::double('div')->addClass('layui-input-block');

        list($openOption, $closeOptions) = $formItemSwitch->getOptions();

        $this->changeEvent($formItemSwitch, $filter);

        // 选中状态
        $checked = $formItemSwitch->getDefault() !== null && $formItemSwitch->getDefault() == $openOption['value'];

        $switch = El::double('input')->setAttrs([
            'lay-skin'       => 'switch',
            'type'           => 'checkbox',
            'checked'        => $checked ? 'checked' : null,
            'active-value'   => $openOption['value'],
            'inactive-value' => $closeOptions['value'],
            'lay-filter'     => $filter,
            'class'          => 'layui-input'
        ]);

        $switchTitle = El::double('div')->setAttr('lay-checkbox')
            ->append(implode('|', [$openOption['label'], $closeOptions['label']]));

        return $base->append($inputBox->append($switch, $switchTitle));
    }

    private function changeEvent(FormItemSwitch|FormItemAttrGetter $formItemSwitch, $filter): void
    {
        $baseVar = $this->getVModel($formItemSwitch);

        $code = JsCode::create('let value = data.value');
        if (isset($formItemSwitch->getEvents()['change'])){
            $code->then($formItemSwitch->getEvents()['change']->code);
        }

        Html::js()->defCodeBlock(JsFunc::call('layui.form.on', "switch($filter)",
            JsFunc::anonymous(['data'],
                $code->thenIf('data.elem.checked',
                    "$baseVar = layui.jquery(data.elem).attr('active-value')",
                    "$baseVar = layui.jquery(data.elem).attr('inactive-value')"
                )
            )
        ));
    }

}