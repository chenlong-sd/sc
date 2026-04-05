<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class PreparedListWidget
{
    /**
     * @param Dialog[] $dialogs
     */
    public function __construct(
        public readonly ?FormRenderState $filterState = null,
        public readonly ?TableRenderState $tableState = null,
        public readonly array $dialogs = [],
    ) {
    }
}
