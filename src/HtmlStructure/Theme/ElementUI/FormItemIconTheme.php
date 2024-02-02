<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemIcon;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\IconSelector;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemIconThemeInterface;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;

class FormItemIconTheme extends AbstractFormItemTheme implements FormItemIconThemeInterface
{
    /**
     * @param FormItemIcon|FormItemAttrGetter $formItemIcon
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemIcon|FormItemAttrGetter $formItemIcon): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemIcon);

        $input = El::double('icon-selector')->setAttrs([
            'v-model' => $this->getVModel($formItemIcon),
        ])->setAttrs($formItemIcon->getVAttrs());

        $this->addEvent($input, $formItemIcon->getEvents(), $formItemIcon->getName());

        Html::js()->vue->addComponents(new IconSelector());

        return $this->afterRender($formItemIcon, $base->append($input));
    }
}