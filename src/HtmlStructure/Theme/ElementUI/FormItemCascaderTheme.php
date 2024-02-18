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
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCascaderThemeInterface;

class FormItemCascaderTheme extends AbstractFormItemTheme implements FormItemCascaderThemeInterface
{
    /**
     * @param FormItemCascader|FormItemAttrGetter $formItemCascader
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemCascader|FormItemAttrGetter $formItemCascader): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemCascader);

        if (!$optionsVar = $formItemCascader->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItemCascader->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $cascader = El::double('el-cascader')->setAttrs([
            'v-model'  => $this->getVModel($formItemCascader),
            'placeholder' => $formItemCascader->getPlaceholder(),
            ':options' => $optionsVar,
            'style'    => 'width:100%'
        ])->setAttrs($formItemCascader->getVAttrs());

        Html::js()->vue->set($optionsVar, $formItemCascader->getOptions());

        $formItemCascader->getCloseAfterSelection() and $this->closeAfterSelection($formItemCascader, $cascader);

        $this->addEvent($cascader, $formItemCascader->getEvents(), $formItemCascader->getName());

        return $this->afterRender($formItemCascader, $base->append($cascader));
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