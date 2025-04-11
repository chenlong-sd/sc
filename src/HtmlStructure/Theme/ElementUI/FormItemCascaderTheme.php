<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCascader;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCustomizeThemeInterface;

class FormItemCascaderTheme extends AbstractFormItemTheme implements FormItemCustomizeThemeInterface
{
    /**
     * @param FormItemCascader|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        if (!$optionsVar = $formItem->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItem->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $cascader = El::double($formItem->isPanel() ? 'el-cascader-panel' : 'el-cascader')->setAttrs([
            'v-model'  => $this->getVModel($formItem),
            'placeholder' => $formItem->getPlaceholder(),
            ':options' => $optionsVar,
            'style'    => 'width:100%'
        ])->setAttrs($formItem->getVAttrs());

        $this->setOptions($formItem, $optionsVar);

        $formItem->getCloseAfterSelection() and $this->closeAfterSelection($formItem, $cascader);

        $this->addEvent($cascader, $formItem->getEvents(), $formItem->getName());

        return $formItem->isPanel() ? $cascader : $base->append($cascader);
    }

    public function closeAfterSelection(FormItemCascader|FormItemAttrGetter $formItemCascader, DoubleLabel $el): void
    {
        if (!$ref = $el->getAttr('ref')) {
            $ref = $formItemCascader->getName() . 'Ref';
            $el->setAttr('ref', $ref);
        }

        $jsFunc = JsFunc::anonymous(["value"]);
        if (isset($formItemCascader->getEvents()['change'])) {
            $event = $formItemCascader->getEvents()['change'];
            if ($event instanceof JsFunc) {
                $jsFunc->appendCode($event->code);
            }else{
                $jsFunc->appendCode(JsFunc::call("this.$event"));
            }
        }
        $jsFunc->appendCode("this.\$refs['$ref'].togglePopperVisible(false)");

        $formItemCascader->event('change', $jsFunc);
    }

}