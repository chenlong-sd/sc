<?php

namespace Sc\Util\HtmlStructureV2\Contracts\Fields;

interface ValidatableFieldInterface
{
    public function isRequired(): bool;

    public function hasRules(): bool;

    public function getRules(): array;
}
