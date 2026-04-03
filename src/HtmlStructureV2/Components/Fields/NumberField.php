<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSearch;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\SearchableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class NumberField extends Field implements SearchableFieldInterface, ValidatableFieldInterface
{
    use HasSearch;
    use HasValidation;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::NUMBER);
    }

    protected function defaultSearchType(): string
    {
        return '=';
    }

    public function minValue(int|float $min): static
    {
        return $this->prop('min', $min);
    }

    public function maxValue(int|float $max): static
    {
        return $this->prop('max', $max);
    }

    public function step(int|float $step): static
    {
        return $this->prop('step', $step);
    }

    public function precision(int $precision): static
    {
        return $this->prop('precision', $precision);
    }
}
