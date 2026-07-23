<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Stringable;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;

final class EventColumn extends SpecialColumn
{
    public static function make(string $title = '操作', string $prop = ''): self
    {
        $column = Column::make($title, $prop)
            ->type('event')
            ->props([
                'mark-event' => 'true',
                'class-name' => 'sc-v2-event-column',
                ':show-overflow-tooltip' => 'false',
            ]);

        return new self($column);
    }

    public function width(int|string $width, bool $showOverflowTooltip = true): self
    {
        $this->toColumnDefinition()->width($width, $showOverflowTooltip);

        return $this;
    }

    public function minWidth(int|string $minWidth): self
    {
        $this->toColumnDefinition()->minWidth($minWidth);

        return $this;
    }

    public function align(string $align): self
    {
        $this->toColumnDefinition()->align($align);

        return $this;
    }

    public function fixed(string $position = 'right'): self
    {
        $this->toColumnDefinition()->fixed($position);

        return $this;
    }

    public function displayFormat(string|Stringable $format): self
    {
        $this->toColumnDefinition()->displayFormat($format);

        return $this;
    }

    public function appendContent(string|AbstractHtmlElement|Stringable $content): self
    {
        $this->toColumnDefinition()->appendContent($content);

        return $this;
    }

    public function addTip(string|Stringable $tip, string|Stringable $icon = 'WarningFilled', array $attrs = []): self
    {
        $this->toColumnDefinition()->addTip($tip, $icon, $attrs);

        return $this;
    }
}
