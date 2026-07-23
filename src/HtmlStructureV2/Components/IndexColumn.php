<?php

namespace Sc\Util\HtmlStructureV2\Components;

final class IndexColumn extends SpecialColumn
{
    public static function make(string $title = '序号'): self
    {
        $column = Column::make($title, '')
            ->type('index')
            ->width(80)
            ->fixed('left')
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
