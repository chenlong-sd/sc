<?php
/**
 * datetime: 2023/6/4 0:33
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsFor;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormThemeInterface;

class FormTheme implements FormThemeInterface
{

    public function render(Form $form): AbstractHtmlElement
    {
        $config = $form->getConfig();
        unset($config['data']);

        if (isset($config['dataUrl'])) {

            $config['v-loading'] = $form->getId() . 'Loading';
            Html::js()->vue->set($form->getId() . 'Loading', true);

            Html::js()->vue->addMethod("{$form->getId()}GetDefaultData", ['id'],
                Axios::get($config['dataUrl'], ['id' => Grammar::mark('id')])
                    ->then(JsFunc::arrow(['{ data }'])->code(<<<JS
                        if (data.code !== 200) return;
                        for (const k in this['{$form->getId()}']){
                            if(data.data.hasOwnProperty(k)) this['{$form->getId()}'][k] =  data.data[k];
                        }
                        this["{$form->getId()}Loading"] = false;
                    JS))
            );

            unset($config['dataUrl']);
        }

        $el = El::double('el-form')
            ->setAttr('ref', $form->getId())
            ->setAttr('@submit.prevent')
            ->setAttr('v-model', $form->getId())
            ->setAttrs($config);

        $el->append(...array_map(fn($v) => $v->render('ElementUI'), $form->getFormItems()));

        // 默认值设置，用函数保存，避免被污染
        Html::js()->vue->addMethod("{$form->getId()}Default", ['defaultValues'], JsCode::make(
            JsVar::assign("this.{$form->getId()}", $form->getDefaults()),
            JsIf::when("defaultValues")->then(
                JsFor::loop("const k in this.{$form->getId()}")->then(
                    JsIf::when("defaultValues.hasOwnProperty(k)")->then(
                        JsVar::assign("this.{$form->getId()}[k]", "@defaultValues[k]")
                    )
                )
            )
        ));
        Html::js()->vue->set($form->getId(), '');
        Html::js()->vue->event('created', sprintf("this.%sDefault();", $form->getId()));

        return $el;
    }
}