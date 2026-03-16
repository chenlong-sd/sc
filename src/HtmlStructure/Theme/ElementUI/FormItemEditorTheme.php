<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
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
        $formModel = (string)($formItemEditor->getFormModel() ?? '');
        $fieldName = (string)($formItemEditor->getName() ?? '');
        $uniqueKey = preg_replace('/[^a-zA-Z0-9_]/', '_', trim($formModel . '_' . $fieldName, '_')) ?: 'default';

        $editorId    = 'sre-editor-' . $uniqueKey;
        $optionsName = 'sreEditorOptions_' . $uniqueKey;
        $instanceKey = $formModel && $fieldName ? ($formModel . '.' . $fieldName) : $uniqueKey;

        $vModel = $this->getVModel($formItemEditor);
        $options = $formItemEditor->getInitOptions();

        $options['instanceKey'] ??= $instanceKey;
        $options['initialHTML'] ??= $vModel ? Js::grammar("this.$vModel || ''") : '';

        $uploadUrl = (string)($formItemEditor->getUploadUrl() ?? '');
        $onChangeSyncCode = $vModel ? "VueApp.{$vModel} = payload.html ?? '';" : '';

        $initCode = Js::code(
            Js::let($optionsName, $options),
            <<<JS
                const __userOnChange = {$optionsName}.onChange;
                {$optionsName}.onChange = (payload) => {
                    try {
                        if (typeof __userOnChange === 'function') __userOnChange(payload);
                    } catch (e) {
                        console.warn(e);
                    }
                    if (!payload) return;
                    {$onChangeSyncCode}
                };
            JS,
            <<<JS
                const __userOnFocus = {$optionsName}.onFocus;
                {$optionsName}.onFocus = (payload) => {
                    try {
                        if (typeof __userOnFocus === 'function') __userOnFocus(payload);
                    } catch (e) {
                        console.warn(e);
                    }
                };
            JS,
            <<<JS
                const __userOnBlur = {$optionsName}.onBlur;
                {$optionsName}.onBlur = (payload) => {
                    try {
                        if (typeof __userOnBlur === 'function') __userOnBlur(payload);
                    } catch (e) {
                        console.warn(e);
                    }
                };
            JS,
            $uploadUrl ? <<<JS
                if (!{$optionsName}.onImageUpload) {
                    {$optionsName}.onImageUpload = async (file, { onProgress } = {}) => {
                        const formData = new FormData();
                        formData.append('file', file);
                        const resp = await axios.post("{$uploadUrl}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                            onUploadProgress: (e) => {
                                if (typeof onProgress !== 'function' || !e || !e.total) return;
                                onProgress(Math.round((e.loaded / e.total) * 100));
                            },
                        });
                        const data = resp && resp.data ? resp.data : null;
                        const url = (data && (data.link || data.data || data.url || data.fileFullPath)) || '';
                        if (!url) throw new Error('上传失败');
                        return url;
                    };
                }
                if (!{$optionsName}.onFileUpload) {
                    {$optionsName}.onFileUpload = async (file, { onProgress } = {}) => {
                        const formData = new FormData();
                        formData.append('file', file);
                        const resp = await axios.post("{$uploadUrl}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                            onUploadProgress: (e) => {
                                if (typeof onProgress !== 'function' || !e || !e.total) return;
                                onProgress(Math.round((e.loaded / e.total) * 100));
                            },
                        });
                        const data = resp && resp.data ? resp.data : null;
                        const url = (data && (data.link || data.data || data.url || data.fileFullPath)) || '';
                        if (!url) throw new Error('上传失败');
                        return url;
                    };
                }
            JS : '',
            <<<JS
                this.__sreEditors = this.__sreEditors || {};
                const __sreEditor = new SimpleRichEditor("#{$editorId}", {$optionsName}).init();
                this.__sreEditors["{$editorId}"] = __sreEditor;
            JS,
            $vModel ? <<<JS
                if (typeof this.\$watch === 'function') {
                    this.\$watch(
                        () => this.{$vModel},
                        (val) => {
                            const next = val == null ? '' : String(val);
                            try {
                                if (__sreEditor && typeof __sreEditor.getHTML === 'function' && typeof __sreEditor.setHTML === 'function') {
                                    if (__sreEditor.getHTML() !== next) __sreEditor.setHTML(next);
                                }
                            } catch (e) {
                                console.warn(e);
                            }
                        }
                    );
                }
            JS : ''
        );

        Html::js()->vue->event('mounted', $initCode, true);

        return El::div()->setId($editorId)->addClass('sre-editor');
    }

    private function resourceLoad(): void
    {
        Html::js()->load(StaticResource::SCEDITOR_JS);
        Html::css()->load(StaticResource::SCEDITOR_CSS);
        Html::js()->load(StaticResource::AXIOS);
    }

}
