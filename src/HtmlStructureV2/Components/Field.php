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

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function prop(string $name, mixed $value): static
    {
        $this->props[$name] = $value;

        return $this;
    }

    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    public function span(int $span): static
    {
        $this->span = $span;

        return $this;
    }

    public function helpText(string $helpText): static
    {
        $this->helpText = $helpText;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function suffixActions(Action ...$actions): static
    {
        $this->suffixActions = array_merge($this->suffixActions, $actions);

        return $this;
    }

    public function suffixContent(string|AbstractHtmlElement|null $content): static
    {
        $this->suffixContent = $content;

        return $this;
    }

    public function visibleWhen(string|JsExpression $expression): static
    {
        $this->visibleWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

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
