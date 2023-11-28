<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemDatetime;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemDatetimeThemeInterface;

class FormItemDatetimeTheme extends AbstractFormItemTheme implements FormItemDatetimeThemeInterface
{
    /**
     * @param FormItemDatetime|FormItemAttrGetter $formItemDatetime
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemDatetime|FormItemAttrGetter $formItemDatetime): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItemDatetime);

        $datetime = El::double('el-date-picker')->setAttrs([
            'v-model' => $this->getVModel($formItemDatetime),
            'placeholder' => $formItemDatetime->getPlaceholder(),
        ])->setAttrs($formItemDatetime->getVAttrs());

        if ($formItemDatetime->getCol()) {
            $datetime->setAttrIfNotExist('style', 'width:100%');
        }

        return $this->afterRender($formItemDatetime, $el->append($datetime));
    }
}