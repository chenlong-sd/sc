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
     *
     * @param int|float $min 最小值。
     * @return static 当前数字字段实例。
     *
     * 示例：
     * `Fields::number('sort', '排序')->minValue(0)`
     */
    public function minValue(int|float $min): static
    {
        return $this->prop('min', $min);
    }

    /**
     * 设置最大值。
     *
     * @param int|float $max 最大值。
     * @return static 当前数字字段实例。
     *
     * 示例：
     * `Fields::number('score', '分值')->maxValue(100)`
     */
    public function maxValue(int|float $max): static
    {
        return $this->prop('max', $max);
    }

    /**
     * 设置步进值。
     *
     * @param int|float $step 步进值。
     * @return static 当前数字字段实例。
     *
     * 示例：
     * `Fields::number('sort', '排序')->step(1)`
     */
    public function step(int|float $step): static
    {
        return $this->prop('step', $step);
    }

    /**
     * 设置小数精度。
     *
     * @param int $precision 小数精度。
     * @return static 当前数字字段实例。
     *
     * 示例：
     * `Fields::number('price', '价格')->precision(2)`
     */
    public function precision(int $precision): static
    {
        return $this->prop('precision', $precision);
    }
}
