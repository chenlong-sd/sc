<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSearch;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\SearchableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class DateField extends Field implements PlaceholderFieldInterface, SearchableFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasSearch;
    use HasValidation;

    public function __construct(string $name, string $label, FieldType $type = FieldType::DATE)
    {
        parent::__construct($name, $label, $type);
    }

    protected function defaultPromptPrefix(): string
    {
        return '请选择';
    }

    protected function validationPromptPrefix(): string
    {
        return '请选择';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return 'change';
    }

    protected function defaultSearchType(): string
    {
        return $this->type() === FieldType::DATE_RANGE ? 'BETWEEN' : '=';
    }

    public function format(string $format): static
    {
        return $this->prop('format', $format);
    }

    public function valueFormat(string $format): static
    {
        return $this->prop('value-format', $format);
    }
}
