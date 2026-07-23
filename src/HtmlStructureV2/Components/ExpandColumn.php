<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Stringable;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class ExpandColumn extends SpecialColumn
{
    public static function make(string $title = ''): self
    {
        return new self(Column::make($title, '')->type('expand'));
    }

    public function displayFormat(Renderable|AbstractHtmlElement|string|Stringable $format): self
    {
        $this->toColumnDefinition()->displayFormat($format);

        return $this;
    }
}
