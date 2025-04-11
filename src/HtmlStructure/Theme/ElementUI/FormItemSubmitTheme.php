<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;
use Sc\Util\HtmlStructure\Html\Js\Obj;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSubmitThemeInterface;

class FormItemSubmitTheme extends AbstractFormItemTheme implements FormItemSubmitThemeInterface
{
    /**
     * @param FormItemSubmit|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $formId = $formItem->getForm()->getId();

        $el = $this->getBaseEl($formItem)->setAttr('submit-sign');

        $submitButton = El::double('el-button')->setAttrs([
            'type'      => 'primary',
            '@click'    => $formId . "Submit",
            'v-loading' => Html::js()->vue->bind($formId . "Loading", false),
            ':disabled' => $formId . "Loading",
        ])->append($formItem->getSubmitText());

        $reset = El::double('el-button')->setAttrs([
            '@click' => $formId . "Reset"
        ])->append($formItem->getResetText());

        $this->resetEvent($formItem, $formId);
        $this->submitEvent($formItem, $formId);

        $el->append($submitButton)->append($formItem->getResetText() ? $reset : '');

        return $el;
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
                    $closeCode = Js::code("parent.$closeCode");
                    if ($closePage['theme'] != 'ElementUI') {
                        $closeCode = Js::code(
                            Js::let('index', '@parent.layer.getFrameIndex(window.name)'),
                            $closeCode
                        );
                    }
                }

                $successHandle = Js::code($formItemSubmit->getSuccessTipCode())
                    ->then($success)
                    ->then("this.{$formId}Reset()")
                    ->then($closeCode);
            }


            $submitHandle = Axios::post(
                url: Js::grammar("data.id ? this.{$formId}UpdateUrl : this.{$formId}CreateUrl"),
                data: "@data"
            )->then(JsFunc::arrow(['{ data }'])->code(
                Js::if('data.code === 200', $successHandle, 'this.$message.error(data.msg)'),
                $formItemSubmit->getFail()
            ))->catch(JsFunc::arrow(['error'])->code(
                Js::code('console.log(error)')->then('this.$message.error(error)')
            ))->finally(JsFunc::arrow()->code(Js::assign("this.{$formId}Loading", false)));
        }

        Html::js()->vue->addMethod($formId . "Submit", [], Js::code(
            Js::let('data', "@this.{$formItemSubmit->getFormModel()}"),
            $formItemSubmit->getForm()->getSubmitHandle(),
            $this->verifyData($formItemSubmit->getForm(), Js::code(
                Js::assign("this.{$formId}Loading", true),
                $submitHandle
            ))
        ),
        );
    }

    private function resetEvent(FormItemSubmit|FormItemAttrGetter $formItemSubmit, string $formId): void
    {
        if (!$resetHandle = $formItemSubmit->getResetHandle()) {
            $resetHandle = JsFunc::call(sprintf("this.%sDefault", $formItemSubmit->getForm()->getId()));
        }

        Html::js()->vue->addMethod($formId . "Reset", [], $resetHandle);
    }

    /**
     * @param Form  $form
     * @param mixed $submitCode
     *
     * @return Obj
     */
    private function verifyData(Form $form, mixed $submitCode): Obj
    {
        return JsFunc::call("this.\$refs.{$form ->getId()}.validate", JsFunc::arrow(['valid'])->code(
            Js::if('!valid')->then("return false;"),
            $submitCode,
        ));
    }
}