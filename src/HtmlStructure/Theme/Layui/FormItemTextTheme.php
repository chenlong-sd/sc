<?php
/**
 * datetime: 2023/6/7 0:31
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;

class FormItemTextTheme extends AbstractFormItemTheme implements FormItemTextThemeInterface
{

    public function render(FormItemText|FormItemAttrGetter $formItemText): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItemText);

        $inputBox = El::double('div')->addClass('layui-input-block');

        $input = El::single('input')->setAttrs([
            'type'         => 'text',
            'name'         => $formItemText->getName(),
            'placeholder'  => $formItemText->getPlaceholder(),
            'autocomplete' => 'off',
            'class'        => 'layui-input',
            'value'        => $formItemText->getDefault()
        ]);

        $inputBox->append($input);

        return $el->append($inputBox);
    }
}