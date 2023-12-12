<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSubmitThemeInterface;
use Sc\Util\Tool;

class FormItemSubmitTheme extends AbstractFormItemTheme implements FormItemSubmitThemeInterface
{
    /**
     * @param FormItemSubmit|FormItemAttrGetter $formItemSubmit
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemSubmit|FormItemAttrGetter $formItemSubmit): AbstractHtmlElement
    {
        $formId = $formItemSubmit->getForm()->getId();

        $el = $this->getBaseEl($formItemSubmit)->setAttr('submit-sign');

        $submitButton = El::double('el-button')->setAttrs([
            'type'      => 'primary',
            '@click'    => $formId . "Submit",
            'v-loading' => Html::js()->vue->bind($formId . "Loading", false)
        ])->append($formItemSubmit->getSubmitText());

        $reset = El::double('el-button')->setAttrs([
            '@click' => $formId . "Reset"
        ])->append($formItemSubmit->getResetText());

        $this->resetEvent($formItemSubmit, $formId);
        $this->submitEvent($formItemSubmit, $formId);

        $el->append($submitButton)->append($formItemSubmit->getResetText() ? $reset : '');

        return $this->afterRender($formItemSubmit, $el);
    }

    private function submitEvent(FormItemSubmit|FormItemAttrGetter $formItemSubmit, string $formId): void
    {
        if (!$submitHandle = $formItemSubmit->getSubmitHandle()){
            Html::js()->vue->set("{$formId}Url", '');
            Html::js()->vue->set("{$formId}CreateUrl", $formItemSubmit->getCreateUrl());
            Html::js()->vue->set("{$formId}UpdateUrl", $formItemSubmit->getUpdateUrl());

            $success = $formItemSubmit->getSuccess();
            if (str_contains($success, '@strict ')){
                $successHandle = preg_replace("/^@strict/", '', $success);
            }else{
                $closePage = $formItemSubmit->getClosePage();
                if ($closePage['theme'] == "ElementUI") {
                    $closeCode = "VueApp.closeWindow()";
                }else{
                    $closeCode = "layer.close(index);";
                }

                if ($closePage['page'] == 'parent') {
                    $closeCode = JsCode::create("parent.$closeCode");
                    if ($closePage['theme'] != 'ElementUI') {
                        $closeCode = JsCode::make(
                            JsVar::def('index', '@parent.layer.getFrameIndex(window.name)'),
                            $closeCode
                        );
                    }
                }

                $successHandle = JsCode::create($formItemSubmit->getSuccessTipCode())
                    ->then($success)
                    ->then("this.{$formId}Reset()")
                    ->then($closeCode);
            }
            $submitHandle = Axios::post(
                url: Grammar::mark("this.{$formId}Url"),
                data: "@data"
            )->then(JsFunc::arrow(['{ data }'])->code(
                JsIf::when('data.code === 200')
                    ->then($successHandle)
                    ->else(JsCode::create('this.$message.error(data.msg)')),
                $formItemSubmit->getFail()
            ))->catch(JsFunc::arrow(['error'])->code(
                JsCode::create('console.log(error)')->then('this.$message.error(error)')
            ))->finally(JsFunc::arrow()->code("this.{$formId}Loading = false;"));
        }

        Html::js()->vue->addMethod($formId ."Submit", [],
            JsCode::create("this.{$formId}Loading = true;")
                ->then(JsVar::def('data',  "@this.{$formItemSubmit->getFormModel()}"))
                ->then($formItemSubmit->getForm()->getSubmitHandle())
                ->then($submitHandle)
        );
    }

    private function resetEvent(FormItemSubmit|FormItemAttrGetter $formItemSubmit, string $formId): void
    {
        if (!$resetHandle = $formItemSubmit->getResetHandle()) {
            $resetHandle = JsFunc::call(sprintf("this.%sDefault", $formItemSubmit->getForm()->getId()));
        }

        Html::js()->vue->addMethod($formId . "Reset", [], $resetHandle);
    }

}