<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Form\AbstractFormItem;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;

/**
 * Class AbstractFormItemTheme
 */
abstract class AbstractFormItemTheme
{

    protected function renderFormItem($formItem): AbstractHtmlElement
    {
        return El::fictitious();
    }

    public function render(FormItemInterface|FormItemAttrGetter $formItem): AbstractHtmlElement
    {
        $el = $this->renderFormItem($formItem);

        return $this->afterRender($formItem, $el);
    }

    public function getBaseEl(FormItemInterface|FormItemAttrGetter $formItem): AbstractHtmlElement
    {
        $el = El::double('el-form-item')
            ->setAttr('label', $formItem->getLabel());

        if ($formItem->getTipsInfo()) {
            $el->append(
                h('template', ['#label' => ''])->append(
                   h('div')->append(
                       $formItem->getLabel()
                   )->append(
                       h('el-tooltip')->append(
                           h('el-icon', h('info-filled'))
                               ->appendStyle("{position: relative; top: 2px;cursor: pointer}")
                       )->setAttrs([
                           'content' => $formItem->getTipsInfo()['tips'],
                           'placement' => 'top',
                           ... $formItem->getTipsInfo()['attrs'] ?? []
                       ])
                   )
                )
            );
        }

        if ($formItem->getLabelWidth() !== null) {
            $el->setAttr('label-width', $formItem->getLabelWidth());
        }
        if ($formItem->getRules()) {
            $el->setAttr("prop", $formItem->getName());
        }
        if ($formItem->getLabelPosition()){
            $el->setAttr('label-position', $formItem->getLabelPosition());
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
    private function afterRender(FormItemInterface|FormItemAttrGetter $formItem, AbstractHtmlElement $el): AbstractHtmlElement
    {
        $el = $this->addCol($formItem, $el);

        if ($formItem->getWhen()){
            $el->eachChildren(function (AbstractHtmlElement $el) use ($formItem) {
                $el->setAttr('v-if', $formItem->getWhen());
            });
        }

        if ($formItem->getVAttrs()) {
            $el->find('[v-model]')?->setAttrs($formItem->getVAttrs());
        }

        if ($formItem->getBeforeRender()) {
            $res = call_user_func($formItem->getBeforeRender(), $el);
            if ($res instanceof AbstractHtmlElement) {
                $el = $res;
            }
        }

        return $el;
    }


    /**
     * @param FormItemAttrGetter|AbstractFormItem $formItem
     * @param string                              $varName
     *
     * @return void
     */
    protected function setOptions(FormItemAttrGetter|AbstractFormItem $formItem, string $varName): void
    {
        Html::js()->vue->set($varName, Html::js()->vue->get($varName, $formItem->getOptions()));

        if ($remote = $formItem->getOptionsRemote()) {
            $dataCode = Js::code(
                Js::assign("this.$varName", "@{$remote['valueCode']}")
            );
            if (!empty($remote['valueName']) || !empty($remote['labelName'])) {
                $dataCode->then(
                    Js::let("map", JsFunc::arrow(['d'])->code(
                        Js::return(Js::call("d.map", JsFunc::arrow(['item'])->code(
                            empty($remote['valueName']) ? "" : Js::assign("item.value", "@item.{$remote['valueName']}"),
                            empty($remote['labelName']) ? "" : Js::assign("item.label", "@item.{$remote['labelName']}"),
                            Js::if("item.children && item.children.length > 0")->then(
                                Js::assign("item.children", Js::call("map", "@item.children"))
                            ),
                            Js::return("item")
                        )))
                    ))
                );

                $dataCode->then(Js::assign("this.$varName", Js::call("map", "@this.$varName")));
            }

            if (empty($formItem->getEvents()['visible-change'])) {
                $formItem->on('visible-change', "refresh{$varName}");
            }

            foreach ($remote['params'] as &$value) {
                if (str_starts_with($value, '@') && !str_starts_with($value, '@this.') && !str_starts_with($value, '@VueApp.')){
                    $value = strtr($value, ['@' => '@this.']);
                }
            }
            $query = [
                'search' => [
                    'search' => $remote['params']
                ]
            ];

            Html::js()->vue->addMethod("refresh" . $varName, ['visible'], Js::code(
                Js::if("!visible")->then(
                    Js::return()
                ),
                Axios::get($remote['url'], $query)->success($dataCode)
            ));

            Html::js()->vue->event('mounted', JsFunc::call("this.refresh{$varName}", 1));
        }
    }

    /**
     * @param FormItemInterface|FormItemAttrGetter $formItem
     * @param AbstractHtmlElement                  $el
     *
     * @return AbstractHtmlElement|DoubleLabel
     */
    private function addCol(FormItemInterface|FormItemAttrGetter $formItem, AbstractHtmlElement $el): DoubleLabel|AbstractHtmlElement
    {
        if (empty($formItem->getForm()?->getConfig()[':inline']) && $el->toHtml() && $formItem->getCol() != -1) {
            $res = El::double('el-col')->setAttr(':span', $formItem->getCol())->append($el);
            if ($formItem->getAfterCol()) {
                $res->after(El::double('el-col')->setAttr(':span', $formItem->getAfterCol()));
            }
            if ($formItem->getOffsetCol()) {
                $res->setAttr(':offset', $formItem->getOffsetCol());
            }
            $el = $res->getParent() ?: $res;
        }

        return $el;
    }
}