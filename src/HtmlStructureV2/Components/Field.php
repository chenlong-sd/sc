<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

abstract class Field implements FormNode
{
    use HasSpan;

    protected mixed $default = null;
    protected array $props = [];
    protected string $helpText = '';
    protected bool $disabled = false;
    protected array $suffixActions = [];
    protected string|AbstractHtmlElement|null $suffixContent = null;
    protected ?JsExpression $visibleWhen = null;
    protected ?JsExpression $disabledWhen = null;

    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly FieldType $type
    ) {
    }

    /**
     * 设置字段默认值，在新建表单或未加载远端数据时生效。
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * 设置单个底层组件属性，适合透传 Element Plus 原生属性。
     * 属性名会原样输出到最终组件上；需要动态绑定时请自行带上 ":" / "@" 前缀，
     * 例如 ":min"、"@change"。该方法不会自动把值包装成 JS 表达式。
     */
    public function prop(string $name, mixed $value): static
    {
        $this->props[$name] = $value;

        return $this;
    }

    /**
     * 批量设置底层组件属性。
     * 规则与 prop() 一致：键名按原样输出，动态属性请自行写成 ":prop" / "@event"。
     */
    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    /**
     * 设置字段占用的 24 栅格宽度，通常与 grid/section 搭配使用。
     */
    public function span(int $span): static
    {
        $this->span = $span;

        return $this;
    }

    /**
     * 设置字段下方的帮助说明文字。
     */
    public function helpText(string $helpText): static
    {
        $this->helpText = $helpText;

        return $this;
    }

    /**
     * 直接禁用当前字段。
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * 在字段右侧追加操作按钮，适合“选择/查看/跳转”类辅助动作。
     */
    public function suffixActions(Action ...$actions): static
    {
        $this->suffixActions = array_merge($this->suffixActions, $actions);

        return $this;
    }

    /**
     * 在字段右侧追加说明内容，可传纯文本或轻量 HTML 元素。
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
     */
    public function disabledWhen(string|JsExpression $expression): static
    {
        $this->disabledWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
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

    public function getSuffixActions(): array
    {
        return $this->suffixActions;
    }

    public function getSuffixContent(): string|AbstractHtmlElement|null
    {
        return $this->suffixContent;
    }

    public function hasSuffix(): bool
    {
        return $this->suffixActions !== []
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
}
