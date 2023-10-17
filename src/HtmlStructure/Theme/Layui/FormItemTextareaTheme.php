<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemTextarea;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextareaThemeInterface;

class FormItemTextareaTheme extends AbstractFormItemTheme implements FormItemTextareaThemeInterface
{
    /**
     * @param FormItemTextarea|FormItemAttrGetter $formItemTextarea
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemTextarea|FormItemAttrGetter $formItemTextarea): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItemTextarea);

        $inputBox = El::double('div')->addClass('layui-input-block');

        $input = El::double('textarea')->setAttrs([
            'name'         => $formItemTextarea->getName(),
            'placeholder'  => $formItemTextarea->getPlaceholder(),
            'class'        => 'layui-textarea',
        ])->append($formItemTextarea->getDefault());

        $inputBox->append($input);

        return $el->append($inputBox);
    }
}