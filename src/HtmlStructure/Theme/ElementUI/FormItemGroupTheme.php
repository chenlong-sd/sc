<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
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
        $el = El::double('el-card')->addClass('vue--form-card');

        Html::css()->addCss(".vue--form-card{margin-bottom:var(--el-card-padding);}");
        Html::css()->addCss(".vue--form-card .el-card__body{padding-bottom:0;}");
        Html::css()->addCss(".el-form-item .vue--form-card{margin-bottom:0;}");
        Html::css()->addCss(".el-form-item .vue--form-card .el-form-item{margin-bottom:18px;}");

        $children  = $formItem->getChildren();

        $row = El::double('el-row')->setAttr(':gutter', 10);

        foreach ($children as $child) {
            $row->append($child->render(Theme::THEME_ELEMENT_UI));
        }

        if ($formItem->getIsArrayValue()) {
            $row->setAttr("v-for", "({$formItem->getName()}_item, index_g) in {$formItem->getFormModel()}.{$formItem->getName()}");
            $row = El::fictitious()->append(
                $row->addClass('g_r')->append(
                    El::elButton()->addClass('g_del')
                        ->setAttr('icon', 'delete')
                        ->setAttr('type','danger')
                        ->setStyle("{height: calc(100% - 19px);width:15px;position: absolute;right: 0;top: 0;padding: 8px 10px;}")
                        ->setAttr('@click', "{$formItem->getFormModel()}_{$formItem->getName()}_del(index_g)")
                ),
                El::elButton("新增一项")
                    ->setAttr('type', 'success')
                    ->setAttr('plain', )
                    ->setAttr('bg', )
                    ->setAttr('icon', 'plus')
                    ->setAttr('@click', "{$formItem->getFormModel()}_{$formItem->getName()}_add")
                    ->setStyle('{width: 100%;margin-bottom: 10px;position: relative;top: -10px;}')
            );

            Html::js()->vue->addMethod("{$formItem->getFormModel()}_{$formItem->getName()}_add", JsFunc::anonymous()->code(
                JsFunc::call("this.{$formItem->getFormModel()}.{$formItem->getName()}.push", current($formItem->getInitDefault() ?: $formItem->getDefault()))
            ));
            Html::js()->vue->addMethod("{$formItem->getFormModel()}_{$formItem->getName()}_del", JsFunc::anonymous(['index'])->code(
                JsFunc::call("this.{$formItem->getFormModel()}.{$formItem->getName()}.splice", '@index', 1)
            ));
        }

        if ($formItem->getPlain()) {
            $el = $row;
            if ($formItem->getLabel()) {
                $el = El::fictitious()->append(
                    FormItem::customize($formItem->getLabel())->render(Theme::THEME_ELEMENT_UI),
                    $el
                );
            }
        }else{
            $el->append($row);
            if ($formItem->getLabel()) {
                $el->append(
                    El::template(El::elText($formItem->getLabel())->setAttr('size', 'large'))->setAttr('#header')
                );
            }
        }

        return $el;
    }
}