<?php

namespace Sc\Util\HtmlStructureV2\Components;

/**
 * Public API base for non-data table columns.
 *
 * The wrapped Column remains the internal representation consumed by the
 * existing table renderer and metadata builders.
 */
abstract class SpecialColumn
{
    protected function __construct(
        private readonly Column $column
    ) {
    }

    /**
     * Convert the public specialized column into the internal column definition.
     *
     * @internal Used by Table::addColumns().
     */
    final public function toColumnDefinition(): Column
    {
        return $this->column;
    }

    /**
     * Pass through raw el-table-column attributes.
     *
     * @param array<string, mixed> $attrs
     */
    public function props(array $attrs): static
    {
        $this->column->props($attrs);

        return $this;
    }

    /** Hide this special column. */
    public function notShow(bool $confirm = true): static
    {
        $this->column->notShow($confirm, false, null);

        return $this;
    }
}
