<?php

namespace Sc\Util\HtmlStructure\Theme\Layui;

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

    public function render(FormItemRadio|FormItemAttrGetter $formItemRadio): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemRadio);
        $inputBox = El::double('div')->addClass('layui-input-block');
        if (!$filter = $formItemRadio->getOptionsVarName()) {
            mt_srand();
            $filter = $formItemRadio->getName() . 'Rand' .  mt_rand(1, 999);
        }
        $radio = [];

        foreach ($formItemRadio->getOptions() as ['value' => $value, 'label' => $label]) {
            $radio[] = El::double('input')->setAttrs([
                'name'       => $formItemRadio->getName(),
                'value'      => $value,
                'title'      => $label,
                'type'       => 'radio',
                'lay-filter' => $filter,
            ]);
        }

        return $base->append($inputBox->append(...$radio));
    }
}