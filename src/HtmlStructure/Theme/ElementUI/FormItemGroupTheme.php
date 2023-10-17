<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemGroup;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemGroupThemeInterface;

class FormItemGroupTheme extends AbstractFormItemTheme implements FormItemGroupThemeInterface
{
    /**
     * @param FormItemGroup|FormItemAttrGetter $formItemGroup
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemGroup|FormItemAttrGetter $formItemGroup): AbstractHtmlElement
    {
        $el = El::double('el-card')->addClass('vue--form-card');

        Html::css()->addCss(".vue--form-card{margin-bottom:var(--el-card-padding);}");
        Html::css()->addCss(".vue--form-card .el-card__body{padding-bottom:0;}");
        Html::css()->addCss(".el-form-item .vue--form-card{margin-bottom:0;}");
        Html::css()->addCss(".el-form-item .vue--form-card .el-form-item{margin-bottom:18px;}");

        $children  = $formItemGroup->getChildren();

        foreach ($children as $child) {
            $el->append($child->render("ElementUI"));
        }

        if ($formItemGroup->getLabel()) {
            $el->append(
                El::double('template')->setAttr('#header')->append(
                    El::double('el-text')->setAttr('size', 'large')->append($formItemGroup->getLabel())
                )
            );
        }

        return $this->afterRender($formItemGroup, $el);
    }
}