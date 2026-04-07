<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class IconField extends TextField
{
    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::ICON);
    }
}
