<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

interface FormNodeContainer extends FormNode
{
    /**
     * @return FormNode[]
     */
    public function childNodes(): array;
}
