<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class EditorField extends TextField
{
    private string $uploadUrl = '';
    private array $initOptions = [];

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
     * `Fields::editor('content', '内容')->uploadUrl('/admin/upload/editor-image')`
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
     * `Fields::editor('content', '内容')->initOptions(['toolbar' => ['bold', 'italic']])`
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
     * `Fields::editor('content', '内容')->option('layout.height', 420)`
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
     * 兼容旧编辑器事件写法。
     * contentChanged/focus/blur 会自动映射到 SimpleRichEditor 的 onChange/onFocus/onBlur。
     *
     * @param string $event 事件名。
     * @param string|JsExpression $handler 事件处理逻辑。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * `Fields::editor('content', '内容')->event('contentChanged', '({ html }) => console.log(html)')`
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
     * 便捷设置编辑区高度。
     *
     * @param int|string $height 编辑区高度。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * `Fields::editor('content', '内容')->height(420)`
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
     * `Fields::editor('content', '内容')->minHeight(320)`
     */
    public function minHeight(int|string $height): static
    {
        return $this->option('layout.minHeight', $height);
    }

    public function getUploadUrl(): string
    {
        return $this->uploadUrl;
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
}
