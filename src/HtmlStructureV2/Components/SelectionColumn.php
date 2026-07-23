<?php

namespace Sc\Util\HtmlStructureV2\Components;

final class SelectionColumn extends SpecialColumn
{
    public static function make(): self
    {
        $column = Column::make('', '')
            ->type('selection')
            ->width(48)
            ->align('center');

        return new self($column);
    }

    public function width(int|string $width, bool $showOverflowTooltip = true): self
    {
        $this->toColumnDefinition()->width($width, $showOverflowTooltip);

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
}
