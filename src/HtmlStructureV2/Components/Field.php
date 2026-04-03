<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

abstract class Field
{
    protected mixed $default = null;
    protected array $props = [];
    protected int $span = 24;
    protected string $helpText = '';
    protected bool $disabled = false;
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

    public function getVisibleWhen(): ?JsExpression
    {
        return $this->visibleWhen;
    }

    public function getDisabledWhen(): ?JsExpression
    {
        return $this->disabledWhen;
    }
}
