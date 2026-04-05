<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;

interface FormNodePathScopedContainer extends FormNodeContainer
{
    public function childPathContext(FormNodePathContext $context): FormNodePathContext;
}
