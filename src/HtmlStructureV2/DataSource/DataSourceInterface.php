<?php

namespace Sc\Util\HtmlStructureV2\DataSource;

interface DataSourceInterface
{
    public function initialRows(): array;

    public function toClientConfig(): array;

    public function isRemote(): bool;
}
