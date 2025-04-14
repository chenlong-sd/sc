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
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemGroupThemeInterface;

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
        if ($formItem->getPlain() && $formItem->getLabel()) {
            $children = [
                FormItem::customize($formItem->getLabel()),
                ...$children
            ];
        }

        $row = El::double('el-row')->setAttr(':gutter', 10);

        foreach ($children as $child) {
            $row->append($child->render("ElementUI"));
        }
        if ($formItem->getPlain()) {
            $el = $row;
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