<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemEditor;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemEditorThemeInterface;

class FormItemEditorTheme extends AbstractFormItemTheme implements FormItemEditorThemeInterface
{
    /**
     * @param FormItemEditor|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $this->resourceLoad();

        $editorEl = $this->initialize($formItem);
        $baseEl   = $this->getBaseEl($formItem);

        return $baseEl->append($editorEl);
    }

    private function initialize(FormItemEditor|FormItemAttrGetter $formItemEditor): AbstractHtmlElement
    {
        $editorId = 'froala-editor' . $formItemEditor->getName();
        $varName  = 'editor' . $formItemEditor->getName();
        $options  = $formItemEditor->getInitOptions();

        $options['events']['contentChanged'] = JsFunc::anonymous([], "VueApp." . "{$this->getVModel($formItemEditor)} = $varName.html.get()");

        // 创建编辑器
        $editor = Js::let($varName, JsFunc::call('new FroalaEditor', "div#{$editorId}",
            $options
            , JsFunc::anonymous([],
                Js::code($formItemEditor->getFullScreen() ? "$varName.fullscreen.toggle()" : '')
                    ->then("$varName.html.set(`{$formItemEditor->getDefault()}`)")
            )));

        Html::js()->vue->event('created', JsFunc::call('setTimeout', JsFunc::arrow([], $editor)));

        return El::div()->setId($editorId);
    }

    private function resourceLoad(): void
    {
        Html::js()->load(StaticResource::FROALA_JS);
        Html::css()->load(StaticResource::FROALA_CSS);
        Html::js()->load(StaticResource::FROALA_LANGUAGE);
    }

}