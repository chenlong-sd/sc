<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlStructure\Form\FormItem;
use Sc\Util\HtmlStructure\Form\FormItemGroup;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemGroupThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

class FormItemGroupTheme extends AbstractFormItemTheme implements FormItemGroupThemeInterface
{
    /**
     * @param FormItemGroup|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $el = El::double('el-card')->addClass('vue--form-card')->setAttr("shadow", $formItem->getShadow());

        Html::css()->addCss(".vue--form-card{margin-bottom:var(--el-card-padding);}");
        Html::css()->addCss(".vue--form-card .el-card__body{padding-bottom:0;position: relative}");
        Html::css()->addCss(".el-form-item .vue--form-card{margin-bottom:0;}");
        Html::css()->addCss(".el-form-item .vue--form-card .el-form-item{margin-bottom:18px;}");

        $children  = $formItem->getChildren();

        $row = El::double('el-row')->setAttr(':gutter', 10);

        foreach ($children as $child) {
            $row->append($child->render(Theme::THEME_ELEMENT_UI));
        }

        if ($formItem->getPlain()) {
            $el = $row;
            if ($formItem->getLabel()) {
                $el = El::fictitious()->append(
                    FormItem::customize(
                        El::div(
                            is_string($formItem->getLabel())
                                ? El::elText($formItem->getLabel())->setAttr('size', 'large')
                                : $formItem->getLabel()
                        )->setAttr("group-item-header")
                    )->render(Theme::THEME_ELEMENT_UI),
                    $el
                );
            }
        }else{
            $el->append($row);
            if ($formItem->getLabel()) {
                $el->append(
                    El::template(
                        is_string($formItem->getLabel())
                            ? El::elText($formItem->getLabel())->setAttr('size', 'large')
                            : $formItem->getLabel()
                    )->setAttr('#header')->setAttr("group-item-header")
                );
            }
        }

        if ($formItem->getIsArrayValue()) {
            $el = $this->multipleHandle($el, $formItem);
        }

        return $el;
    }

    /**
     * @param DoubleLabel $row
     * @param FormItemGroup|FormItemAttrGetter $formItem
     * @return AbstractHtmlElement
     */
    public function multipleHandle(DoubleLabel $row, FormItemGroup|FormItemAttrGetter $formItem): AbstractHtmlElement
    {
        if ($formItem->getLabel()) {
            if (!$row->find("[group-item-header]")){
                $row->append(h('template', ['#header' => '']));
            }
            $row->find("[group-item-header]")->append(
                h('el-button', '删除')->setAttrs([
                    'type' => 'danger',
                    'icon' => 'delete',
                    'text' => '',
                    'style' => 'float: right;'
                ])->setAttr('@click', "{$formItem->getFormModel()}_{$formItem->getName()}_del({$formItem->getFormModel()}.{$formItem->getName()}, {$formItem->getName()}_index)")
            );
        }else{
            $row->find('el-card')?->append(
                El::elButton()->addClass('g_del')
                    ->setAttr('icon', 'delete')
                    ->setAttr('type', 'danger')
                    ->setStyle("{height: calc(100%);width:15px;position: absolute;right: 0;top: 0;padding: 8px 10px;}")
                    ->setAttr('@click', "{$formItem->getFormModel()}_{$formItem->getName()}_del({$formItem->getFormModel()}.{$formItem->getName()}, {$formItem->getName()}_index)")
            );
        }

        $addHandleButton = is_string($formItem->getArrayAddText())
            ? El::elButton($formItem->getArrayAddText())
                ->setAttr('type', 'success')
                ->setAttr('plain',)
                ->setAttr('bg',)
                ->setAttr('icon', 'plus')
                ->setStyle('{width: 100%;margin-bottom: 10px;position: relative;top: -10px;}')
            : h($formItem->getArrayAddText());

        $addHandleButton->setAttr('@click', "{$formItem->getFormModel()}_{$formItem->getName()}_add({$formItem->getFormModel()}.{$formItem->getName()})");

        $row = h() ->append(
            h('template', $row)->setAttr("v-for", "({$formItem->getName()}_item, {$formItem->getName()}_index) in {$formItem->getFormModel()}.{$formItem->getName()}"),
            h(' <el-divider style="margin: 50px auto;width: 60%"><el-text>暂无数据</el-text></el-divider>')
                ->setAttr("v-if", "{$formItem->getFormModel()}.{$formItem->getName()}.length === 0"),
            $addHandleButton
        );

        Html::js()->vue->addMethod("{$formItem->getFormModel()}_{$formItem->getName()}_add", JsFunc::anonymous(['item'])->code(
            JsFunc::call("item.push", $formItem->getInitDefault())
        ));
        Html::js()->vue->addMethod("{$formItem->getFormModel()}_{$formItem->getName()}_del", JsFunc::anonymous(['item', 'index'])->code(
            JsFunc::call("item.splice", '@index', 1)
        ));

        return $row;
    }
}