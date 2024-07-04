<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSwitch;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSwitchThemeInterface;

class FormItemSwitchTheme extends AbstractFormItemTheme implements FormItemSwitchThemeInterface
{
    /**
     * @param FormItemSwitch|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        list($openOption, $closeOptions) = $formItem->getOptions();

        $input = El::double('el-switch')->setAttrs([
            'v-model'         => $this->getVModel($formItem),
            'inline-prompt'   => '',
            'active-text'     => $openOption['label'],
            'inactive-text'   => $closeOptions['label'],
            ':active-value'   => $openOption['value'],
            ':inactive-value' => $closeOptions['value'],
        ])->setAttrs($formItem->getVAttrs());

        $this->addEvent($input, $formItem->getEvents(), $formItem->getName());

        return $base->append($input);
    }
}