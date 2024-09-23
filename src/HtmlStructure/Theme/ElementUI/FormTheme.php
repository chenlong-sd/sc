<?php
/**
 * datetime: 2023/6/4 0:33
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormThemeInterface;

class FormTheme implements FormThemeInterface
{

    public function render(Form $form): AbstractHtmlElement
    {
        $config = $form->getConfig();
        unset($config['data']);

        $config = $this->defaultDataHandle($form, $config);

        $el = El::double('el-form')
            ->setAttr('ref', $form->getId())
            ->setAttr(':rules', $this->rulesHandle($form))
            ->setAttr('@submit.prevent')
            ->setAttr(':model', $form->getId())
            ->setAttrs($config);

        $items  = array_map(fn($v) => $v->render('ElementUI'), $form->getFormItems());
        $el->append(El::double('el-row')->append(...$items));

        return $el;
    }

    /**
     * @param Form $form
     *
     * @return string
     */
    public function rulesHandle(Form $form): string
    {
        $rules = [];
        /** @var Form\FormItemAttrGetter|Form\FormItemInterface $item */
        foreach ($form->getFormItems() as $item) {
            if ($rule = $item->getRules()) {
                $item->getName()
                    ? $rules[$item->getName()] = $rule
                    : $rules = array_merge($rules, $rule);
            }
        }

        Html::js()->vue->set($form->getId() . 'Rules', $rules);

        return $form->getId() . 'Rules';
    }

    /**
     * @param Form  $form
     * @param array $config
     *
     * @return array
     */
    private function defaultDataHandle(Form $form, array $config): array
    {
        $loadingVar          = $form->getId() . 'Loading';
        $config['v-loading'] = $loadingVar;
        Html::js()->vue->set($loadingVar, false);

        if (isset($config['dataUrl'])) {
            Html::js()->vue->addMethod("{$form->getId()}GetDefaultData", ['id'],
                Axios::get($config['dataUrl'], ['id' => Js::grammar('id')])
                    ->then(JsFunc::arrow(['{ data }'])->code(<<<JS
                        if (data.code !== 200) return;
                        for (const k in this['{$form->getId()}']){
                            if(data.data.hasOwnProperty(k)) this['{$form->getId()}'][k] =  data.data[k];
                        }
                        this["$loadingVar"] = false;
                    JS
                    ))
            );

            unset($config['dataUrl']);
        }

        // 默认值设置，用函数保存，避免被污染
        Html::js()->vue->addMethod("{$form->getId()}Default", ['defaultValues'], Js::code(
            Js::assign("this.{$form->getId()}", $form->getDefaults()),
            Js::if("defaultValues")->then(
                Js::for("const k in this.{$form->getId()}")->then(
                    Js::if("defaultValues.hasOwnProperty(k)")->then(
                        Js::assign("this.{$form->getId()}[k]", "@defaultValues[k]")
                    )
                )
            )
        ));
        Html::js()->vue->set($form->getId(), '');
        Html::js()->vue->event('created', sprintf("this.%sDefault();", $form->getId()));

        return $config;
    }
}