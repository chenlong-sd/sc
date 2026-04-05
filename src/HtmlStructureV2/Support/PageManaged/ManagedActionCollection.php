<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Action;

final class ManagedActionCollection
{
    /**
     * @param Action[] $actions
     */
    public function __construct(
        public readonly array $actions,
        public readonly string $owner,
    ) {
    }
}
