<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class TableRenderState
{
    public function __construct(
        public readonly string $key,
        public readonly TableRenderBindings $bindings,
    ) {
    }
}
