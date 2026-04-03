<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

class BasicField extends Field
{
    public function __construct(string $name, string $label, FieldType $type = FieldType::TEXT)
    {
        parent::__construct($name, $label, $type);
    }
}
