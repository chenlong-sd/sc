<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

class TextField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    public function __construct(string $name, string $label, FieldType $type = FieldType::TEXT)
    {
        parent::__construct($name, $label, $type);
    }

    public function email(?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'type' => 'email',
            'message' => $message ?: '请输入正确的' . $this->label(),
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    public function phone(?string $message = null, string|array|null $trigger = null): static
    {
        return $this->pattern(
            '/^1[3-9]\d{9}$/',
            $message ?: '请输入正确的' . $this->label(),
            $trigger
        );
    }

    public function pattern(string|JsExpression $pattern, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'pattern' => $pattern instanceof JsExpression ? $pattern : JsExpression::make($pattern),
            'message' => $message ?: $this->label() . '格式不正确',
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    public function minLength(int $length, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'min' => $length,
            'message' => $message ?: $this->label() . '长度不能少于 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    public function maxLength(int $length, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'max' => $length,
            'message' => $message ?: $this->label() . '长度不能超过 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    public function lengthBetween(int $min, int $max, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'min' => $min,
            'max' => $max,
            'message' => $message ?: $this->label() . '长度需在 ' . $min . ' 到 ' . $max . ' 之间',
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }
}
