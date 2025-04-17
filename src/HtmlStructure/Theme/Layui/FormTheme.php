<?php
/**
 * datetime: 2023/6/7 0:14
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\Obj;
use Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormThemeInterface;

class FormTheme implements FormThemeInterface
{

    public function render(Form $form): AbstractHtmlElement
    {
        $el = El::double('form')
            ->setAttr('lay-filter', $form->getId())
            ->setId($form->getId())
            ->addClass('layui-form');

        Html::js()->defVar($form->getId(), $form->getDefaults());

        $el->append(...array_map(fn($v) => $v->render('Layui'), $form->getFormItems()));

        Html::js()->defCodeBlock(Obj::use('layui.form')->call('val', $form->getId(), Grammar::mark($form->getId())));

        return $el;
    }
}