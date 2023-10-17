<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSwitch;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSwitchThemeInterface;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;

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
        $base = $this->getBaseEl($formItemSwitch);

        list($openOption, $closeOptions) = $formItemSwitch->getOptions();

        $input = El::double('el-switch')->setAttrs([
            'v-model'         => $this->getVModel($formItemSwitch),
            'inline-prompt'   => '',
            'active-text'     => $openOption['label'],
            'inactive-text'   => $closeOptions['label'],
            ':active-value'   => $openOption['value'],
            ':inactive-value' => $closeOptions['value'],
        ])->setAttrs($formItemSwitch->getVAttrs());

        $this->addEvent($input, $formItemSwitch->getEvents(), $formItemSwitch->getName());


        return $this->afterRender($formItemSwitch, $base->append($input));
    }
}