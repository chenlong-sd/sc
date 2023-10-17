<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemTextarea;
use Sc\Util\HtmlStructure\Html\Html;
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
        $base = $this->getBaseEl($formItemTextarea);

        $input = El::double('el-input')->setAttrs([
            'v-model'     => $this->getVModel($formItemTextarea),
            'placeholder' => $formItemTextarea->getPlaceholder(),
            'type'        => 'textarea',
            ':rows'       => 4
        ])->setAttrs($formItemTextarea->getVAttrs());

        $this->addEvent($input, $formItemTextarea->getEvents(), $formItemTextarea->getName());

        return $this->afterRender($formItemTextarea, $base->append($input));
    }
}