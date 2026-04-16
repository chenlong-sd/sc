<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasFormTableColumnAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

abstract class Field implements FormNode
{
    use HasFormTableColumnAttributes;
    use HasSpan;

    protected mixed $default = null;
    protected array $props = [];
    protected string $helpText = '';
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected array $suffixActions = [];
    protected string|AbstractHtmlElement|null $suffixContent = null;
    protected ?JsExpression $visibleWhen = null;
    protected ?JsExpression $disabledWhen = null;
    protected ?JsExpression $readonlyWhen = null;
    protected ?string $labelWidth = null;

    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly FieldType $type
    ) {
    }

    /**
     * 设置字段默认值，在新建表单或未加载远端数据时生效。
     *
     * @param mixed $default 默认值。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->default('默认标题')`
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * 设置单个底层组件属性，适合透传 Element Plus 原生属性。
     * 属性名会原样输出到最终组件上；需要动态绑定时请自行带上 ":" / "@" 前缀，
     * 例如 ":min"、"@change"。
     * 若属性名以 ":" 开头：
     * - 传字符串时按原样作为前端表达式输出
     * - 传数组/布尔/数字/null 时会自动转成 JS 字面量
     *
     * @param string $name 属性名，可带 ":" 或 "@" 前缀。
     * @param mixed $value 属性值。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->prop('clearable', true)`
     */
    public function prop(string $name, mixed $value): static
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }

        if ($value === null || (!str_starts_with($name, ':') && $value === false)) {
            unset($this->props[$name]);

            return $this;
        }

        if (!str_starts_with($name, ':') && $value === true) {
            $value = '';
        }

        if (($name === 'class' || $name === 'style') && is_string($value)) {
            $value = $this->mergeFieldPropString($name, $value);
        }

        $this->props[$name] = $value;

        return $this;
    }

    /**
     * 批量设置底层组件属性。
     * 规则与 prop() 一致：键名按原样输出，动态属性请自行写成 ":prop" / "@event"；
     * 其中 ":" 开头属性的数组/布尔/数字/null 值会自动按 JS 字面量处理。
     *
     * @param array $props 要批量合并的属性。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->props(['clearable' => true, 'maxlength' => 100])`
     */
    public function props(array $props): static
    {
        foreach ($props as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $this->prop($name, $value);
        }

        return $this;
    }

    /**
     * 设置字段组件根节点的单个属性。
     * 这是 prop() 的语义化别名，便于与 Blocks / 布局节点 / 表单结构节点保持统一写法。
     * 属性会直接透传到字段实际渲染的组件根节点，不作用于外层 form-item。
     *
     * @param string $name 属性名，可带 `":prop"` 或 `"@event"` 前缀。
     * @param mixed $value 属性值。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->attr(':maxlength', 50)`
     */
    public function attr(string $name, mixed $value = ''): static
    {
        return $this->prop($name, $value);
    }

    /**
     * 批量设置字段组件根节点属性。
     * 这是 props() 的语义化别名，便于在使用侧统一使用 attrs 风格 API。
     *
     * @param array $attributes 属性集合。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->attrs(['clearable' => true, ':maxlength' => 50])`
     */
    public function attrs(array $attributes): static
    {
        return $this->props($attributes);
    }

    /**
     * 追加字段组件根节点的 class。
     * 多次调用会自动合并，等价于设置组件的 `class` 属性。
     *
     * @param string|null $className class 名称；传 null 可移除。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->className('title-input')`
     */
    public function className(?string $className): static
    {
        return $this->prop('class', $className);
    }

    /**
     * 追加字段组件根节点的 style。
     * 多次调用会自动合并，等价于设置组件的 `style` 属性。
     *
     * @param string|null $style 内联样式；传 null 可移除。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->style('max-width:320px')`
     */
    public function style(?string $style): static
    {
        return $this->prop('style', $style);
    }

    private function mergeFieldPropString(string $name, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return (string)($this->props[$name] ?? '');
        }

        $current = trim((string)($this->props[$name] ?? ''));
        if ($current === '') {
            return $value;
        }

        if ($name === 'style') {
            return rtrim($current, '; ') . '; ' . ltrim($value, '; ');
        }

        return trim($current . ' ' . $value);
    }

    /**
     * 设置字段占用的 24 栅格宽度，通常与 grid/section 搭配使用。
     *
     * @param int $span 栅格宽度，通常取 1-24。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->span(12)`
     */
    public function span(int $span): static
    {
        $this->span = $span;

        return $this;
    }

    /**
     * 设置字段下方的帮助说明文字。
     *
     * @param string $helpText 帮助说明。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('slug', '标识')->helpText('仅支持英文、数字和中划线')`
     */
    public function helpText(string $helpText): static
    {
        $this->helpText = $helpText;

        return $this;
    }

    /**
     * 直接禁用当前字段。
     *
     * @param bool $disabled 是否禁用，默认值为 true。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('code', '编码')->disabled()`
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * 设置字段只读。
     * 支持 readonly 的组件会输出 `readonly`；
     * 不支持 readonly 的组件会自动退化为 `disabled`。
     *
     * @param bool $readonly 是否只读，默认值为 true。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('code', '编码')->readonly()`
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * 在字段右侧追加操作按钮，适合“选择/查看/跳转”类辅助动作。
     *
     * @param Action ...$actions 要追加的右侧动作。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('user_name', '用户')->suffixActions(Actions::make('选择'))`
     */
    public function suffixActions(Action ...$actions): static
    {
        $this->suffixActions = array_merge($this->suffixActions, $actions);

        return $this;
    }

    /**
     * 在字段右侧追加说明内容，可传纯文本或轻量 HTML 元素。
     *
     * @param string|AbstractHtmlElement|null $content 右侧补充内容。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('code', '编码')->suffixContent('自动生成')`
     */
    public function suffixContent(string|AbstractHtmlElement|null $content): static
    {
        $this->suffixContent = $content;

        return $this;
    }

    /**
     * 按条件控制字段显示，表达式上下文中的 model 指向当前表单数据。
     * 传入字符串时会按原样作为前端 JS 表达式注入，不会再包裹引号。
     * 在 object/arrayGroup/table 等子作用域中，`model` 会自动切到当前子模型。
     * 这里只有 `model` 是默认可用变量，不会额外注入 row / tableKey / listKey / vm。
     * 例如：`model.type === "custom"`、`model.user?.status === 1`。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('other', '其他')->visibleWhen('model.type === \"other\"')`
     */
    public function visibleWhen(string|JsExpression $expression): static
    {
        $this->visibleWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按条件控制字段禁用，表达式上下文中的 model 指向当前表单数据。
     * 用法与 visibleWhen() 一致，只是最终作用到组件的 `disabled` 状态。
     * 默认同样只有 `model` 可用。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->disabledWhen('model.locked === true')`
     */
    public function disabledWhen(string|JsExpression $expression): static
    {
        $this->disabledWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按条件控制字段只读，表达式上下文中的 model 指向当前表单数据。
     * 支持 readonly 的组件会绑定 `readonly`；
     * 其余组件会自动退化为 `disabled`。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->readonlyWhen('model.locked === true')`
     */
    public function readonlyWhen(string|JsExpression $expression): static
    {
        $this->readonlyWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 设置当前字段的 label 宽度，覆盖表单级别的 label-width。
     *
     * @param int|string $width label 宽度；数字会自动补上 px 单位，
     *                           字符串按原样处理（如 '120px'、'auto'）。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->labelWidth(120)`
     */
    public function labelWidth(int|string $width): static
    {
        if (is_int($width)) {
            $width = $width . 'px';
        } elseif (is_numeric($width) && trim($width) !== '') {
            $width = trim($width) . 'px';
        }

        $this->labelWidth = $width;

        return $this;
    }

    public function getLabelWidth(): ?string
    {
        return $this->labelWidth;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function hasLabel(): bool
    {
        return trim($this->label) !== '';
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function type(): FieldType
    {
        return $this->type;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function getSpan(): int
    {
        return $this->span;
    }

    public function getHelpText(): string
    {
        return $this->helpText;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function getSuffixActions(): array
    {
        return array_values(array_filter(
            $this->suffixActions,
            static fn (Action $action): bool => $action->isAvailable()
        ));
    }

    public function getSuffixContent(): string|AbstractHtmlElement|null
    {
        return $this->suffixContent;
    }

    public function hasSuffix(): bool
    {
        return $this->getSuffixActions() !== []
            || ($this->suffixContent !== null && $this->suffixContent !== '');
    }

    public function getVisibleWhen(): ?JsExpression
    {
        return $this->visibleWhen;
    }

    public function getDisabledWhen(): ?JsExpression
    {
        return $this->disabledWhen;
    }

    public function getReadonlyWhen(): ?JsExpression
    {
        return $this->readonlyWhen;
    }
}
