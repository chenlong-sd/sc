<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

use Sc\Util\HtmlStructureV2\Components\Dialog;

interface StructuredEventInterface extends \JsonSerializable
{
    /**
     * @return Dialog[]
     */
    public function referencedDialogs(): array;

    /**
     * @return string[]
     */
    public function referencedDialogKeys(): array;

    /**
     * @return string[]
     */
    public function referencedTableKeys(): array;

    /**
     * @return string[]
     */
    public function referencedListKeys(): array;
}
