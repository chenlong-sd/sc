<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSelect;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemSelectTheme extends AbstractFormItemTheme implements FormItemSelectThemeInterface
{

    public function render(FormItemSelect|FormItemAttrGetter $formItemSelect): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemSelect);

        $select = El::double('el-select')->setAttrs([
            'v-model'     => $this->getVModel($formItemSelect),
            'placeholder' => $formItemSelect->getPlaceholder(),
            'clearable'   => '',
            'filterable'  => ''
        ]);
        $select->setAttrs($formItemSelect->getVAttrs());

        if (!$optionsVar = $formItemSelect->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItemSelect->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $options = El::double('el-option')->setAttrs([
            'v-for'  => "(item, index) in $optionsVar",
            ':key'   => "item.value",
            ':value' => "item.value",
            ':label' => "item.label",
        ]);

        if ($formItemSelect->getOptions() && !array_search($formItemSelect->getDefault(), $formItemSelect->getOptions())) {
            $formItemSelect->default(null);
        }

        if ($formItemSelect->getMultiple()) {
            $select->setAttr('multiple');
        }

        $this->addEvent($select, $formItemSelect->getEvents(), $formItemSelect->getName());

        Html::js()->vue->set($optionsVar, $formItemSelect->getOptions());

        return $this->afterRender($formItemSelect, $base->append($select->append($options)));
    }
}