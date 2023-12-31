<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemInLine;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemInLineThemeInterface;

class FormItemInLineTheme extends AbstractFormItemTheme implements FormItemInLineThemeInterface
{
    /**
     * @param FormItemInLine|FormItemAttrGetter $formItemInLine
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemInLine|FormItemAttrGetter $formItemInLine): AbstractHtmlElement
    {
        $el = El::double('el-form-item')->setAttr('label-width', 0);

        $children  = $formItemInLine->getChildren();
        $col       = $this->colCalc($children);

        foreach ($children as $child) {
            $elCol = El::double('el-col')
                ->setAttr(':span', $child->getCol() ?: $col)
                ->append($child->render("ElementUI"));
            $el->append($elCol);
        }

        return $this->afterRender($formItemInLine, $el);
    }

    /**
     * @param array $children
     *
     * @return int
     */
    private function colCalc(array $children): int
    {
        $total      = 24;
        $waitColumn = 0;

        foreach ($children as $child) {
            if ($col = $child->getCol()) {
                $total -= $col;
            }else{
                $waitColumn++;
            }
        }

        return $waitColumn ? (int)floor($total / $waitColumn) : $total;
    }
}