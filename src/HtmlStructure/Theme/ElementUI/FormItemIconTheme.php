<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemIcon;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\IconSelector;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemIconThemeInterface;

class FormItemIconTheme extends AbstractFormItemTheme implements FormItemIconThemeInterface
{
    /**
     * @param FormItemIcon|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        $input = El::double('icon-selector')->setAttrs([
            'v-model' => $this->getVModel($formItem),
        ])->setAttrs($formItem->getVAttrs());

        $this->addEvent($input, $formItem->getEvents(), $formItem->getName());

        Html::js()->vue->addComponents(new IconSelector());

        return $base->append($input);
    }
}