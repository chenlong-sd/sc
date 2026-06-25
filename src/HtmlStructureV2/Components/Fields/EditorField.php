<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class EditorField extends TextField
{
    public const VALUE_MODE_HTML = 'html';
    public const VALUE_MODE_SUBMIT_PAYLOAD = 'submit';
    public const VALUE_MODE_PUBLISH_PAYLOAD = 'publish';

    private string $uploadUrl = '';
    private array $initOptions = [];
    private string $valueMode = self::VALUE_MODE_HTML;
    private array $payloadOptions = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::EDITOR);
    }

    /**
     * 设置编辑器内图片/附件上传接口。
     * 若未显式提供 onImageUpload/onFileUpload，会自动基于该地址生成默认上传处理。
     *
     * @param string $uploadUrl 编辑器上传接口地址。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->uploadUrl('/admin/upload/editor-image')`
     */
    public function uploadUrl(string $uploadUrl): static
    {
        $this->uploadUrl = trim($uploadUrl);

        return $this;
    }

    /**
     * 批量设置 SimpleRichEditor 初始化选项。
     * 支持直接传 JsExpression 作为回调函数值，例如 onChange / onFocus / onBlur。
     *
     * @param array $options 初始化选项。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->initOptions(['toolbar' => ['bold', 'italic']])`
     */
    public function initOptions(array $options): static
    {
        $this->initOptions = array_replace_recursive($this->initOptions, $options);

        return $this;
    }

    /**
     * 设置单个编辑器初始化选项。
     *
     * @param string $key 选项键，支持 layout.xxx 形式。
     * @param mixed $value 选项值。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->option('layout.height', 420)`
     */
    public function option(string $key, mixed $value): static
    {
        if (str_starts_with($key, 'layout.')) {
            $segments = explode('.', $key);
            $target = &$this->initOptions;

            foreach ($segments as $segment) {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }

                $target = &$target[$segment];
            }

            $target = $value;

            return $this;
        }

        $this->initOptions[$key] = $value;

        return $this;
    }

    /**
     * 设置编辑器事件处理函数。
     * contentChanged/focus/blur 会自动映射到 SimpleRichEditor 的 onChange/onFocus/onBlur。
     *
     * @param string $event 事件名。
     * @param string|JsExpression $handler 事件处理逻辑。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->event('contentChanged', '({ html }) => console.log(html)')`
     */
    public function event(string $event, string|JsExpression $handler): static
    {
        $mappedEvent = [
            'contentChanged' => 'onChange',
            'focus' => 'onFocus',
            'blur' => 'onBlur',
        ][$event] ?? $event;

        $this->initOptions[$mappedEvent] = $handler instanceof JsExpression
            ? $handler
            : JsExpression::make($handler);

        return $this;
    }

    /**
     * 设置表单模型中保存的富文本值形态。
     *
     * 默认 `html` 与旧版行为一致，只保存 HTML 字符串。
     * `submit` 会保存 SimpleRichEditor::getSubmitPayload() 的结构化提交载荷。
     * `publish` 会保存 SimpleRichEditor::getPublishPayload() 的发布载荷，包含 publishHtml。
     *
     * @param string $mode html / submit / publish。
     * @param array $payloadOptions getSubmitPayload()/getPublishPayload() 选项。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content')->valueMode(EditorField::VALUE_MODE_PUBLISH_PAYLOAD, ['article' => ['inlineCSS' => true]])`
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
     * 示例：
     * - `Fields::editor('content')->submitPayload(['includeText' => true])`
     *
     * @param array $options getSubmitPayload() 选项。
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
     * 示例：
     * - `Fields::editor('content')->publishPayload(['article' => ['inlineCSS' => true]])`
     *
     * @param array $options getPublishPayload() 选项。
     */
    public function publishPayload(array $options = []): static
    {
        return $this->valueMode(self::VALUE_MODE_PUBLISH_PAYLOAD, $options);
    }

    /**
     * 便捷设置编辑区高度。
     *
     * @param int|string $height 编辑区高度。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->height(420)`
     */
    public function height(int|string $height): static
    {
        return $this->option('layout.height', $height);
    }

    /**
     * 便捷设置编辑区最小高度。
     *
     * @param int|string $height 编辑区最小高度。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->minHeight(320)`
     */
    public function minHeight(int|string $height): static
    {
        return $this->option('layout.minHeight', $height);
    }

    public function getUploadUrl(): string
    {
        if ($this->uploadUrl !== '') {
            return $this->uploadUrl;
        }

        if (!function_exists('sc_default_upload_url')) {
            return '';
        }

        try {
            return (string)\sc_default_upload_url();
        } catch (\Throwable) {
            return '';
        }
    }

    public function getEditorOptions(): array
    {
        $options = $this->initOptions;

        if (isset($options['height']) && !isset($options['layout']['height'])) {
            $options['layout']['height'] = $options['height'];
            unset($options['height']);
        }

        $options['layout'] = array_merge([
            'width' => '100%',
            'height' => 600,
        ], is_array($options['layout'] ?? null) ? $options['layout'] : []);

        return array_merge([
            'placeholder' => $this->getPlaceholder() ?: '请输入内容...',
            'enablePasteDebug' => false,
            'enableDraftAutosave' => false,
            'draftRestorePrompt' => false,
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
