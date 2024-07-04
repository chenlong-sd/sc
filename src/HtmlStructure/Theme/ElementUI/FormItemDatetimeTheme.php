<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemDatetime;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemDatetimeThemeInterface;

class FormItemDatetimeTheme extends AbstractFormItemTheme implements FormItemDatetimeThemeInterface
{
    /**
     * @param FormItemDatetime|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItem);

        $datetime = El::double('el-date-picker')->setAttrs([
            'v-model' => $this->getVModel($formItem),
            'placeholder' => $formItem->getPlaceholder(),
        ])->setAttrs($formItem->getVAttrs());

        $datetime->setAttrIfNotExist('type', 'datetime');

        $this->formatHandle($datetime);

        if ($formItem->getCol()) {
            $datetime->setAttrIfNotExist('style', 'width:100%');
        }

        return $el->append($datetime);
    }

    /**
     * YYYY-MM-DD  HH:mm:ss
     *
     * @param DoubleLabel $datetime
     *
     * @return void
     */
    private function formatHandle(DoubleLabel $datetime): void
    {
        if ($datetime->getAttr('format')) {
            $datetime->setAttrIfNotExist('value-format', $datetime->getAttr('format'));
            return;
        }
        $format = match ($datetime->getAttr('type')){
            'date', 'dates' => 'YYYY-MM-DD',
            default =>  'YYYY-MM-DD HH:mm:ss'
        };

        $datetime->setAttrIfNotExist('format', $format);
        $datetime->setAttrIfNotExist('value-format', $format);
    }
}