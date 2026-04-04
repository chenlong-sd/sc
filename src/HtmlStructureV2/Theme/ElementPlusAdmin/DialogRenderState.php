<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class DialogRenderState
{
    public function __construct(
        public readonly string $formModel,
        public readonly string $visibleModel,
        public readonly FormRenderOptions $formOptions,
        public readonly ?DialogRenderBindings $bindings = null,
    ) {
    }
}
