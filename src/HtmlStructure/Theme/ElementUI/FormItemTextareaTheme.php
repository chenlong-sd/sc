<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemTextarea;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextareaThemeInterface;

class FormItemTextareaTheme extends AbstractFormItemTheme implements FormItemTextareaThemeInterface
{
    /**
     * @param FormItemTextarea|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        $input = El::double('el-input')->setAttrs([
            'v-model'     => $this->getVModel($formItem),
            'placeholder' => $formItem->getPlaceholder(),
            'type'        => 'textarea',
            ':rows'       => 4
        ])->setAttrs($formItem->getVAttrs());

        $this->addEvent($input, $formItem->getEvents(), $formItem->getName());

        return $base->append($input);
    }
}