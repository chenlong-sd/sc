<?php

namespace Sc\Util\HtmlStructureV2\DataSource;

final class ArrayDataSource implements DataSourceInterface
{
    public function __construct(
        private readonly array $rows
    ) {
    }

    public static function make(array $rows): self
    {
        return new self($rows);
    }

    public function initialRows(): array
    {
        return $this->rows;
    }

    public function toClientConfig(): array
    {
        return [
            'type' => 'array',
            'rows' => $this->rows,
        ];
    }

    public function isRemote(): bool
    {
        return false;
    }
}
