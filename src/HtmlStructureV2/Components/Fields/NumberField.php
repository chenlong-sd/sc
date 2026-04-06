<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class NumberField extends Field implements ValidatableFieldInterface
{
    use HasValidation;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::NUMBER);
    }

    /**
     * 设置最小值。
     */
    public function minValue(int|float $min): static
    {
        return $this->prop('min', $min);
    }

    /**
     * 设置最大值。
     */
    public function maxValue(int|float $max): static
    {
        return $this->prop('max', $max);
    }

    /**
     * 设置步进值。
     */
    public function step(int|float $step): static
    {
        return $this->prop('step', $step);
    }

    /**
     * 设置小数精度。
     */
    public function precision(int $precision): static
    {
        return $this->prop('precision', $precision);
    }
}
