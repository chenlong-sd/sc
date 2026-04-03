<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Table;

final class Tables
{
    public static function make(string $key): Table
    {
        return Table::make($key);
    }

    public static function column(string $label, string $prop): Column
    {
        return Column::make($label, $prop);
    }
}
