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
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;
use Sc\Util\ScTool;
use Sc\Util\Tool;

class FormItemTextTheme extends AbstractFormItemTheme implements FormItemTextThemeInterface
{
    /**
     * @param FormItemText|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        if ($formItem->getOptions() || $formItem->getAutoCompleteHandle()) {
            return $this->autoCompleteRender($formItem);
        }

        $render = $this->textRender($formItem);

        if ($formItem->isNumber()) {
            $this->numberHandle($render, $formItem);
        }

        return $render;
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

        if (!empty($formItemText->getForm()?->getConfig()[':inline'])
            || !empty($formItemText->getForm()?->getConfig()['inline'])
        ){
            $input->appendStyle("{width:192px}");
        }

        $this->addEvent($input, $formItemText->getEvents(), $formItemText->getName());

        return $base->append($input);
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

        return $base->append($autoComplete);
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
        Html::js()->vue->addMethod($search, ['searchStr', 'cb'], Js::code(
            Js::let('res', []),
            Js::for("let i = 0; i < this.{$search}Data.length; i++")->then(
                Js::if("this.{$search}Data[i].value.includes(searchStr)", "res.push(this.{$search}Data[i])")
            ),
            Js::code("return cb(res);")
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
        ])->success("cb(data.data)");

        Html::js()->vue->addMethod($search, ['searchStr', 'cb'], $axios);
    }

    /**
     * @param AbstractHtmlElement             $render
     * @param FormItemText|FormItemAttrGetter $formItemText
     *
     * @return void
     */
    private function numberHandle(AbstractHtmlElement $render, FormItemText|FormItemAttrGetter $formItemText): void
    {
        $input = $render->find('[v-model]');
        if ($input->getAttr('@change')) {
            return;
        }
        $precision  = $formItemText->getNumberPrecision();
        $methodName = ScTool::random('changeNumber')->get();

        $input->setAttr('@change', $methodName);

        Html::js()->vue->addMethod($methodName, JsFunc::anonymous(['val'])->code(
            Js::code('const newValue = parseFloat(val)'),
            Js::if('!isNaN(newValue)',"val = newValue", "val = 0"),
            Js::if("$precision >= 0", "val = val.toFixed($precision)"),
            Js::assign("this." . $input->getAttr('v-model'), '@val')
        ));
    }

}