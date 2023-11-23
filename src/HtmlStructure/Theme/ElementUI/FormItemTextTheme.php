<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFor;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;
use Sc\Util\Tool;

class FormItemTextTheme extends AbstractFormItemTheme implements FormItemTextThemeInterface
{
    /**
     * @param FormItemText|FormItemAttrGetter $formItemText
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemText|FormItemAttrGetter $formItemText): AbstractHtmlElement
    {
        if ($formItemText->getOptions() || $formItemText->getAutoCompleteHandle()) {
            return $this->autoCompleteRender($formItemText);
        }

        return $this->textRender($formItemText);
    }

    /**
     * @param FormItemText|FormItemAttrGetter $formItemText
     *
     * @return AbstractHtmlElement
     */
    private function textRender(FormItemText|FormItemAttrGetter $formItemText): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemText);

        $input = El::double('el-input')->setAttrs([
            'v-model'     => $this->getVModel($formItemText),
            'placeholder' => $formItemText->getPlaceholder(),
            'clearable'   => ''
        ])->setAttrs($formItemText->getVAttrs());

        $this->addEvent($input, $formItemText->getEvents(), $formItemText->getName());

        return $this->afterRender($formItemText, $base->append($input));
    }

    /**
     * @param FormItemText|FormItemAttrGetter $formItemText
     *
     * @return AbstractHtmlElement
     */
    private function autoCompleteRender(FormItemText|FormItemAttrGetter $formItemText): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemText);
        $search = Tool::random('auto')->get();

        $autoComplete = El::double('el-autocomplete')->setAttrs([
            'v-model'            => $this->getVModel($formItemText),
            ':fetch-suggestions' => $search,
            'placeholder'        => $formItemText->getPlaceholder(),
            'clearable'          => '',
            'style'              => 'width:100%'
        ])->setAttrs($formItemText->getVAttrs());

        $autoCompleteHandle = $formItemText->getAutoCompleteHandle();
        if (!$autoCompleteHandle){
            $this->optionsSearch($search, $formItemText);
        }else{
            $this->searchHandle($search, $autoCompleteHandle);
        }

        if ($format = $formItemText->getAutoCompleteFormat()){
            $autoComplete->append($format);
        }

        return $this->afterRender($formItemText, $base->append($autoComplete));
    }

    /**
     * @param string                          $search
     * @param FormItemText|FormItemAttrGetter $formItemText
     *
     * @return void
     */
    private function optionsSearch(string $search, FormItemText|FormItemAttrGetter $formItemText): void
    {
        Html::js()->vue->set($search . 'Data', $formItemText->getOptions());
        Html::js()->vue->addMethod($search, ['searchStr', 'cb'], JsCode::make(
            JsVar::def('res', []),
            JsFor::loop("let i = 0; i < this.{$search}Data.length; i++")->then(
                JsIf::when("this.{$search}Data[i].value.includes(searchStr)")
                    ->then("res.push(this.{$search}Data[i])")
            ),
            JsCode::make("return cb(res);")
        ));
    }

    private function searchHandle(string $search, $autoCompleteHandle): void
    {
        if ($autoCompleteHandle instanceof JsFunc){
            Html::js()->vue->addMethod($search, $autoCompleteHandle);
            return;
        }

        $axios = Axios::get($autoCompleteHandle, [
            'search' => '@searchStr'
        ])->success(JsCode::make("cb(data.data)"));

        Html::js()->vue->addMethod($search, ['searchStr', 'cb'], $axios);
    }

}