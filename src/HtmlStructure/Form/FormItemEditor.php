<?php
/**
 * datetime: 2023/6/7 23:20
 **/

namespace Sc\Util\HtmlStructure\Form;

use InvalidArgumentException;
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

    public const VALUE_MODE_HTML = 'html';
    public const VALUE_MODE_SUBMIT_PAYLOAD = 'submit';
    public const VALUE_MODE_PUBLISH_PAYLOAD = 'publish';

    private array $initOptions = [];
    private string $valueMode = self::VALUE_MODE_HTML;
    private array $payloadOptions = [];

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

    /**
     * 设置表单模型中保存的富文本值形态。
     *
     * 默认 html 与旧版行为一致，只保存 HTML 字符串。
     * submit 会保存 SimpleRichEditor::getSubmitPayload() 的结构化提交载荷。
     * publish 会保存 SimpleRichEditor::getPublishPayload() 的发布载荷，包含 publishHtml。
     *
     * @param string $mode html / submit / publish
     * @param array $payloadOptions getSubmitPayload()/getPublishPayload() 选项
     * @return $this
     */
    public function valueMode(string $mode, array $payloadOptions = []): static
    {
        $mode = trim($mode);
        if (!in_array($mode, [
            self::VALUE_MODE_HTML,
            self::VALUE_MODE_SUBMIT_PAYLOAD,
            self::VALUE_MODE_PUBLISH_PAYLOAD,
        ], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported editor value mode [%s]. Supported modes: html, submit, publish.',
                $mode
            ));
        }

        $this->valueMode = $mode;
        $this->payloadOptions = $payloadOptions;

        return $this;
    }

    /**
     * 表单模型保存 SimpleRichEditor::getSubmitPayload() 返回值。
     *
     * 返回值用于后台长期保存和再次编辑，通常包含 html / document / generatedAt 等字段。
     *
     * 参数说明：
     * - includeText：追加纯文本摘要 text，便于搜索、预览或后端校验。
     * - includeMarkdown：追加 markdown 字段，便于同时保留 Markdown 视图内容。
     * - includeDiagnostics：追加 diagnostics 诊断信息，便于接入期排查格式、来源和恢复策略；正式提交通常不需要开启。
     *
     * @param array $options getSubmitPayload() 选项。
     * @return $this
     */
    public function submitPayload(array $options = []): static
    {
        return $this->valueMode(self::VALUE_MODE_SUBMIT_PAYLOAD, $options);
    }

    /**
     * 表单模型保存 SimpleRichEditor::getPublishPayload() 返回值。
     *
     * 返回值用于文章/新闻发布场景，会在 submitPayload 的基础上追加 publishHtml。
     * html 用于再次编辑，publishHtml 用于详情页展示缓存。
     *
     * 参数说明：
     * - article.inlineCSS：将文章展示样式内联到 publishHtml；适合详情页不方便额外加载 CSS 的场景。
     * - article.wrapperTag：设置 publishHtml 外层包裹标签，默认由编辑器决定。
     * - article.wrapperClass：设置 publishHtml 外层包裹 class，通常保持 sre-article。
     * - includeText：追加纯文本摘要 text，便于搜索、预览或后端校验。
     * - includeMarkdown：追加 markdown 字段，便于同时保留 Markdown 视图内容。
     * - includeDiagnostics：追加 diagnostics 诊断信息，便于接入期排查；正式提交通常不需要开启。
     *
     * @param array $options getPublishPayload() 选项。
     * @return $this
     */
    public function publishPayload(array $options = []): static
    {
        return $this->valueMode(self::VALUE_MODE_PUBLISH_PAYLOAD, $options);
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
            'height' => 600,
        ], $options['layout'] ?? []);

        return array_merge([
            'placeholder'          => $this->placeholder ?: '请输入内容...',
            'enablePasteDebug'     => false,
            'enableDraftAutosave'  => false,
            'draftRestorePrompt'   => false,
        ], $options);
    }

    public function getValueMode(): string
    {
        return $this->valueMode;
    }

    public function getPayloadOptions(): array
    {
        return $this->payloadOptions;
    }
}
