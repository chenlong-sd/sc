<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;

final class PreparedListWidget
{
    /**
     * @param Dialog[] $dialogs
     */
    public function __construct(
        public readonly ?Form $filterForm = null,
        public readonly ?FormRenderState $filterState = null,
        public readonly ?TableRenderState $tableState = null,
        public readonly array $dialogs = [],
    ) {
    }
}
