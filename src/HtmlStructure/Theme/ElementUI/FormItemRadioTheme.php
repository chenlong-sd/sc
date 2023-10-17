<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemRadio;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemRadioThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemRadioTheme extends AbstractFormItemTheme implements FormItemRadioThemeInterface
{

    public function render(FormItemRadio|FormItemAttrGetter $formItemRadio): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemRadio);

        if (!$optionsVar = $formItemRadio->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItemRadio->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $checkbox = El::double('el-radio')->setAttrs([
            'v-for'   => "(item, index) in $optionsVar",
            'v-model' => $this->getVModel($formItemRadio),
            ':label'  => 'item.value'
        ])->append('{{ item.label }}')->setAttrs($formItemRadio->getVAttrs());

        $this->addEvent($checkbox, $formItemRadio->getEvents(), $formItemRadio->getName());

        Html::js()->vue->set($optionsVar, $formItemRadio->getOptions());

        return $this->afterRender($formItemRadio, $base->append($checkbox));
    }
}