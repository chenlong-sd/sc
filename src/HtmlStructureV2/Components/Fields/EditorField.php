<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\JsValueEncoder;

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
     * 设置编辑器内图片/视频/附件上传接口。
     * 若未显式提供 onImageUpload/onVideoUpload/onFileUpload，会自动基于该地址生成默认上传处理。
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
     *
     * 传入键名与 `public/sceditor/使用文档.md` 中
     * `new SimpleRichEditor(selector, options)` 的 options 一致。
     * 适合一次性配置 `layout`、`toolbar`、`templates`、`comments`、
     * `versionHistory`、`draftSync`、`collaboration`、`ai`、`forms`
     * 这类对象型参数；如果只是改一个简单键，尤其是 `layout.xxx`
     * 这类单项，优先使用 option()。
     *
     * 对象型参数直接按编辑器原生层级传嵌套数组，不需要手工拍平成
     * `layout.height`、`comments.enabled` 这类路径。常见写法：
     * - `layout`：控制宽高、内边距、工具栏控件高度等，例如
     *   `['width' => '100%', 'height' => 480, 'padding' => 20]`
     * - `comments`：控制批注功能，例如
     *   `['enabled' => true, 'authorName' => '运营']`
     * - `versionHistory`：控制历史版本，例如
     *   `['enabled' => true, 'maxEntries' => 50, 'autoSnapshot' => true]`
     * - `draftSync`：远端草稿同步；`load/save/clear` 这类子项如果是函数，
     *   也要用 `JsExpression`
     * - `ai` / `collaboration`：继续按编辑器文档传子配置对象；对象里的
     *   `request`、`stream`、`beforeAIRequest`、`redactAIContext`、
     *   `loadSnapshot`、`submitBatch`、`subscribe` 等函数值同样需要
     *   `JsExpression`
     *
     * 标量配置可直接传字符串 / 数字 / 布尔；函数型配置如 `onChange`、`onFocus`、
     * `onBlur`、`onImageUpload`、`onVideoUpload`、`onFileUpload`
     * 需要用 JsExpression 包装，否则会被当成普通字符串。
     * 注意：只要某个值最终在前端应当是“可执行函数”，即使它位于二级 / 三级对象中，
     * 也需要显式包成 `JsExpression::make(...)`。
     *
     * 多次调用会递归合并；列表型配置如 `toolbar.items`、`templates`、
     * `slashCommands` 建议一次传完整数组，避免按索引覆盖。
     * 合并规则为：
     * - 关联数组：按键递归覆盖，未提及的同级键保留；
     * - 列表数组：按索引递归合并，因此通常应一次传完整列表；
     * - 若希望明确替换某个对象块，也建议把该对象的完整目标结构一次传入。
     *
     * @param array $options 初始化选项。
     * @return static 当前编辑器字段实例。
     *
     * 示例：
     * - `Fields::editor('content', '内容')->initOptions(['layout' => ['height' => 420, 'padding' => 20], 'versionHistory' => ['enabled' => true, 'maxEntries' => 50]])`
     * - `Fields::editor('content', '内容')->initOptions(['draftSync' => ['load' => JsExpression::make('async ({ instanceKey }) => null'), 'save' => JsExpression::make('async (payload) => payload')]])`
     * - `Fields::editor('content', '内容')->initOptions(['onChange' => JsExpression::make('({ html }) => console.log(html)')])`
     *
     * 完整版示例：
     *   use Sc\Util\HtmlStructureV2\Dsl\Fields;
     *   use Sc\Util\HtmlStructureV2\Support\JsExpression;
     *
     *   $field = Fields::editor('content', '正文')
     *       ->uploadUrl('/admin/upload/editor')
     *       ->initOptions([
     *           'placeholder' => '请输入正文',
     *           'layout' => [
     *               'width' => 'min(1100px, 100%)',
     *               'height' => 520,
     *               'padding' => 20,
     *               'toolbarControlHeight' => 36,
     *           ],
     *           'enableDraftAutosave' => true,
     *           'draftAutosaveDelay' => 800,
     *           'draftRestorePrompt' => true,
     *           'templates' => [
     *               ['id' => 'summary', 'label' => '摘要', 'html' => '<p><strong>摘要：</strong></p><p><br></p>'],
     *               ['id' => 'faq', 'label' => 'FAQ', 'html' => '<h2>常见问题</h2><p><br></p>'],
     *           ],
     *           'comments' => [
     *               'enabled' => true,
     *               'authorName' => '运营',
     *           ],
     *           'versionHistory' => [
     *               'enabled' => true,
     *               'maxEntries' => 50,
     *               'autoSnapshot' => true,
     *           ],
     *           'draftSync' => [
     *               'prefer' => 'newer',
     *               'load' => JsExpression::make('async ({ instanceKey }) => null'),
     *               'save' => JsExpression::make('async ({ instanceKey, html, document, savedAt }) => ({ html, document, savedAt })'),
     *               'clear' => JsExpression::make('async ({ instanceKey }) => undefined'),
     *           ],
     *           'ai' => [
     *               'enabled' => true,
     *               'requestUrl' => '/api/ai/editor',
     *               'model' => 'gpt-4.1-mini',
     *               'beforeAIRequest' => JsExpression::make('(context) => context'),
     *           ],
     *           'onChange' => JsExpression::make('({ html, mode, source }) => console.log(mode, source, html)'),
     *       ]);
     */
    public function initOptions(array $options): static
    {
        $this->initOptions = array_replace_recursive($this->initOptions, $options);

        return $this;
    }

    /**
     * 合并 layout 配置。
     * 等价于 `->initOptions(['layout' => $options])`，适合宽高、内边距等布局项。
     *
     * 常用参数：
     * - `width`：编辑器整体宽度，如 `100%`、`960px`、`min(1100px, 100%)`
     * - `height`：编辑器整体高度；数字按 `px` 处理
     * - `minHeight`：富文本区最小高度
     * - `textModeMinHeight`：源码 / Markdown 区最小高度
     * - `padding`：编辑区内边距
     * - `toolbarControlHeight`：工具栏按钮 / 下拉框高度
     * - `heightAdaptive`：固定高度场景下是否启用自适应伸缩
     *
     * @param array $options layout 子配置。
     */
    public function layout(array $options): static
    {
        return $this->mergeObjectOption('layout', $options);
    }

    /**
     * 合并 toolbar 配置。
     * 等价于 `->initOptions(['toolbar' => $options])`。
     *
     * 常用参数：
     * - `hide`：从默认工具栏中隐藏的按钮 ID 列表，如 `['fontSize', 'table']`
     * - `items`：富文本工具栏项目和顺序，`|` 表示分隔线
     *
     * 说明：
     * - `items` 只控制富文本主工具栏；
     * - `hide` 会对共享区 / 富文本区生效；
     * - 若要完全自定义模板按钮是否显示，记得把 `template` 放进 `items`，或不要把它放进 `hide`。
     *
     * @param array $options toolbar 子配置。
     */
    public function toolbar(array $options): static
    {
        return $this->mergeObjectOption('toolbar', $options);
    }

    /**
     * 设置模板列表。
     * 等价于 `->initOptions(['templates' => $templates])`。
     *
     * 模板项结构：
     * - `id`：模板唯一标识
     * - `label`：工具栏下拉中显示的文案
     * - `html`：插入到当前光标位置的 HTML 片段
     *
     * 传数组时适合静态模板；传 `JsExpression` 时适合在前端动态拼接，
     * 例如 `SimpleRichEditor.getBuiltInTemplates().concat([...])`。
     *
     * @param array|JsExpression $templates 模板列表或前端表达式。
     */
    public function templates(array|JsExpression $templates): static
    {
        return $this->setOptionValue('templates', $templates);
    }

    /**
     * 启用编辑器内置模板。
     *
     * 编辑器内置模板“存在但不会默认启用”；调用本方法后才会把
     * `SimpleRichEditor.getBuiltInTemplates()` 注入到 `templates` 配置中。
     *
     * 若当前已配置 `templates()` 或 `initOptions(['templates' => ...])`，
     * 会在内置模板后继续拼接已有模板；传入 `$extraTemplates` 时会再把这些模板追加到末尾。
     *
     * 额外模板项结构同 `templates()`：
     * - `id`
     * - `label`
     * - `html`
     *
     * @param array $extraTemplates 额外追加到内置模板后的模板列表。
     */
    public function useBuiltInTemplates(array $extraTemplates = []): static
    {
        $current = $this->initOptions['templates'] ?? null;
        $currentExpression = null;
        $hasBuiltInTemplates = false;

        if ($current instanceof JsExpression) {
            $currentExpression = trim($current->expression());
            $hasBuiltInTemplates = str_contains($currentExpression, 'SimpleRichEditor.getBuiltInTemplates()');
        }

        $expression = $hasBuiltInTemplates
            ? ($currentExpression ?: 'SimpleRichEditor.getBuiltInTemplates()')
            : 'SimpleRichEditor.getBuiltInTemplates()';

        if (!$hasBuiltInTemplates) {
            if ($current instanceof JsExpression) {
                $expression .= '.concat(' . $current->expression() . ')';
            } elseif (is_array($current) && $current !== []) {
                $expression .= '.concat(' . JsValueEncoder::encode($current) . ')';
            }
        }

        if ($extraTemplates !== []) {
            $expression .= '.concat(' . JsValueEncoder::encode($extraTemplates) . ')';
        }

        return $this->templates(JsExpression::make($expression));
    }

    /**
     * 合并 comments 配置。
     * 等价于 `->initOptions(['comments' => $options])`。
     *
     * 常用参数：
     * - `enabled`：是否启用批注
     * - `authorName`：默认批注作者名
     * - `load`：加载批注数组的前端函数
     * - `save`：保存批注数组的前端函数
     *
     * 其中 `load/save` 若需要自定义，都应使用 `JsExpression` 传函数。
     *
     * @param array $options comments 子配置。
     */
    public function comments(array $options): static
    {
        return $this->mergeObjectOption('comments', $options);
    }

    /**
     * 合并 versionHistory 配置。
     * 等价于 `->initOptions(['versionHistory' => $options])`。
     *
     * 常用参数：
     * - `enabled`：是否启用版本历史
     * - `maxEntries`：最大历史条数
     * - `autoSnapshot`：是否自动创建快照
     * - `autoSnapshotInterval`：自动快照间隔，单位毫秒
     *
     * @param array $options versionHistory 子配置。
     */
    public function versionHistory(array $options): static
    {
        return $this->mergeObjectOption('versionHistory', $options);
    }

    /**
     * 合并 draftSync 配置。
     * 等价于 `->initOptions(['draftSync' => $options])`。
     *
     * 常用参数：
     * - `prefer`：冲突时优先策略，支持 `newer`、`remote`、`local`
     * - `load`：加载远端草稿的前端函数
     * - `save`：保存远端草稿的前端函数
     * - `clear`：清理远端草稿的前端函数
     *
     * `load/save/clear` 都需要用 `JsExpression` 传函数体。
     *
     * @param array $options draftSync 子配置。
     */
    public function draftSync(array $options): static
    {
        return $this->mergeObjectOption('draftSync', $options);
    }

    /**
     * 合并 collaboration 配置。
     * 等价于 `->initOptions(['collaboration' => $options])`。
     *
     * 常用参数：
     * - `enabled`：是否启用协作
     * - `docId` / `clientId` / `sessionId` / `userId` / `userName`：协作身份信息
     * - `loadSnapshot`：加载协作文档快照
     * - `submitBatch`：提交本地操作批次
     * - `subscribe`：订阅远端事件
     * - `onStatusChange`：协作状态变化通知
     * - `autoSubmit` / `autoSubmitDelay`：自动提交策略
     * - `autoReconnect` / `reconnectDelay`：自动重连策略
     * - `autoRebaseOnReject`：服务端拒绝时是否自动 rebase
     *
     * 所有函数型子项都应使用 `JsExpression`。
     *
     * @param array $options collaboration 子配置。
     */
    public function collaboration(array $options): static
    {
        return $this->mergeObjectOption('collaboration', $options);
    }

    /**
     * 合并 ai 配置。
     * 传 `true/false` 时会映射为 `['enabled' => true/false]`。
     *
     * 常用参数：
     * - `enabled`：是否启用 AI 入口
     * - `provider`：提供方标识
     * - `requestUrl` / `apiKey` / `model`：基础请求配置
     * - `actions`：允许的动作列表
     * - `maxContextLength` / `nearbyBlockCount` / `nearbyBlockLength`：上下文截取配置
     * - `requestTimeout`：请求超时，单位毫秒
     * - `allowFullDocument`：是否允许整篇文档进入上下文
     * - `request` / `stream`：自定义请求函数
     * - `beforeAIRequest` / `redactAIContext`：请求前加工 / 脱敏回调
     *
     * 传布尔时适合只做开关控制；传数组时适合补充完整 AI 子配置。
     *
     * @param array|bool $options ai 子配置，或是否启用 AI。
     */
    public function ai(array|bool $options = true): static
    {
        if (is_bool($options)) {
            $options = ['enabled' => $options];
        }

        return $this->mergeObjectOption('ai', $options);
    }

    /**
     * 合并 forms 配置。
     * 等价于 `->initOptions(['forms' => $options])`。
     *
     * 常用参数：
     * - `mode`：`edit` 或 `fill`
     * - `onSubmit`：填写态提交函数
     *
     * 其中 `onSubmit` 需要使用 `JsExpression`。
     *
     * @param array $options forms 子配置。
     */
    public function forms(array $options): static
    {
        return $this->mergeObjectOption('forms', $options);
    }

    /**
     * 合并 sanitizeExtraAttributes 配置。
     * 等价于 `->initOptions(['sanitizeExtraAttributes' => $options])`。
     *
     * 配置结构为“标签 => 允许追加的属性列表”，例如：
     * - `['abbr' => ['title']]`
     * - `['span' => ['data-custom', 'data-id']]`
     *
     * 只用于在默认白名单之外追加允许的属性，不负责移除默认已允许属性。
     *
     * @param array $options sanitizeExtraAttributes 子配置。
     */
    public function sanitizeExtraAttributes(array $options): static
    {
        return $this->mergeObjectOption('sanitizeExtraAttributes', $options);
    }

    /**
     * 设置单个编辑器初始化选项。
     * 适合设置单个简单键，尤其是 `layout.xxx` 这类嵌套项；复杂对象配置优先用 initOptions()。
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

    private function mergeObjectOption(string $key, array $options): static
    {
        $current = $this->initOptions[$key] ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        $this->initOptions[$key] = array_replace_recursive($current, $options);

        return $this;
    }

    private function setOptionValue(string $key, mixed $value): static
    {
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
