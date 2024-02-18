<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Html\Html;

/**
 * Class AbstractFormItemTheme
 */
abstract class AbstractFormItemTheme
{
    public function getBaseEl(FormItemInterface|FormItemAttrGetter $formItem): AbstractHtmlElement
    {
        $el = El::double('el-form-item')->setAttr('label', $formItem->getLabel());

        if ($formItem->getLabelWidth() !== null) {
            $el->setAttr('label-width', $formItem->getLabelWidth());
        }
        if ($formItem->getRules()) {
            $el->setAttr("prop", $formItem->getName());
        }

        return $el;
    }


    public function getVModel(FormItemInterface|FormItemAttrGetter $formItem): ?string
    {
        return $formItem->getName() ? implode('.', array_filter([$formItem->getFormModel(), $formItem->getName()])) : null;
    }


    public function addEvent(AbstractHtmlElement $element, array $events, string $prefix = ''): void
    {
        foreach ($events as $event => $handle){
            if (is_string($handle)) {
                $element->setAttr('@' . $event, $handle);
                continue;
            }
            $name = $prefix . "__" . $event;
            $element->setAttr('@' . $event, $name);
            Html::js()->vue->addMethod($name, $handle);
        }
    }

    /**
     * @param FormItemInterface|FormItemAttrGetter $formItem
     * @param AbstractHtmlElement                  $el
     *
     * @return AbstractHtmlElement
     */
    public function afterRender(FormItemInterface|FormItemAttrGetter $formItem, AbstractHtmlElement $el): AbstractHtmlElement
    {
        if (empty($formItem->getForm()?->getConfig()[':inline']) && $el->toHtml()) {
            $res = El::double('el-col')->setAttr(':span', $formItem->getCol())->append($el);
            if ($formItem->getAfterCol()) {
                $res->after(El::double('el-col')->setAttr(':span', $formItem->getAfterCol()));
            }
            $el = $res->getParent() ?: $res;
        }

        if ($formItem->getWhen()){
            $el->setAttr('v-if', $formItem->getWhen());
        }

        return $el;
    }
}