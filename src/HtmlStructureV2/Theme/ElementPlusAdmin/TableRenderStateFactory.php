<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class TableRenderStateFactory
{
    public function create(string $tableKey): TableRenderState
    {
        return new TableRenderState(
            key: $tableKey,
            bindings: new TableRenderBindings($tableKey),
        );
    }
}
