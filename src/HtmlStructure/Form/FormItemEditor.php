<?php
/**
 * datetime: 2023/6/7 23:20
 **/

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\UploadUrl;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemEditorThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

class FormItemEditor extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct,  DefaultValue, Placeholder, LabelWidth, FormOrigin, UploadUrl;

    private array $initOptions = [];

    /**
     * 初始化选项
     *
     * @param array $options
     * @link https://froala.com/wysiwyg-editor/docs/options
     *
     * @return $this
     */
    public function initOptions(array $options): static
    {
        $this->initOptions = $options;

        return $this;
    }

    /**
     * 事件
     *
     * @param string $event
     * @param JsFunc $handler
     *
     * @return $this
     *@link https://froala.com/wysiwyg-editor/docs/event
     *
     */
    public function event(string $event, JsFunc $handler): static
    {
        $this->initOptions['events'][$event] = $handler;

        return $this;
    }


    public function render(string $theme = null): AbstractHtmlElement
    {
        // 隐藏logo
        Html::css()->addCss('#fr-logo{ display: none; }.fr-popup.fr-active{ z-index: 5 !important; }');

        return Theme::getRenderer(FormItemEditorThemeInterface::class, $theme)->render($this);
    }

    /**
     * @return array
     */
    public function getInitOptions(): array
    {
        return array_merge([
            'language'       => 'zh_cn',
            'height'         => 400,
            'imageUploadURL' => $this->uploadUrl,
            'fileUploadURL'  => $this->uploadUrl,
            'videoUploadURL' => $this->uploadUrl,
        ], $this->initOptions);
    }
}