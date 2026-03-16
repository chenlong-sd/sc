<?php
/**
 * datetime: 2023/6/7 23:20
 **/

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\UploadUrl;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemEditorThemeInterface;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextareaThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

class FormItemEditor extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct,  DefaultValue, Placeholder, LabelWidth, FormOrigin, UploadUrl;

    private array $initOptions = [];

    /**
     * 初始化选项
     *
     * @param array $options
     * @return $this
     */
    public function initOptions(array $options): static
    {
        $this->initOptions = $options;

        return $this;
    }

    /**
     * 事件（兼容旧 Froala 的 event 写法）
     *
     * @param string $event
     * @param JsFunc $handler
     *
     * @return $this
     *
     */
    public function event(string $event, JsFunc $handler): static
    {
        $map = [
            // Froala -> SimpleRichEditor
            'contentChanged' => 'onChange',
            'focus'          => 'onFocus',
            'blur'           => 'onBlur',
        ];

        $eventKey = $map[$event] ?? $event;

        // SimpleRichEditor style
        if (in_array($eventKey, ['onChange', 'onFocus', 'onBlur', 'onInit', 'onDestroy'], true)) {
            $this->initOptions[$eventKey] = $handler;
            return $this;
        }

        // Legacy passthrough (kept for compatibility; new editor may ignore it)
        $this->initOptions['events'][$event] = $handler;

        return $this;
    }


    public function render(string $theme = null): AbstractHtmlElement
    {
        try {
            return Theme::getRenderer(FormItemEditorThemeInterface::class, $theme)->render($this);
        } catch (\Throwable $throwable) {
            return Theme::getRenderer(FormItemTextareaThemeInterface::class, $theme)->render($this);
        }
    }

    /**
     * @return array
     */
    public function getInitOptions(): array
    {
        $options = $this->initOptions;

        // Legacy mapping: `height` -> `layout.height`
        if (isset($options['height']) && !isset($options['layout']['height'])) {
            $options['layout']['height'] = $options['height'];
            unset($options['height']);
        }

        $options['layout'] = array_merge([
            'width'  => '100%',
            'height' => 400,
        ], $options['layout'] ?? []);

        return array_merge([
            'placeholder'          => $this->placeholder ?: '请输入内容...',
            'enablePasteDebug'     => false,
            'enableDraftAutosave'  => false,
            'draftRestorePrompt'   => false,
        ], $options);
    }
}
