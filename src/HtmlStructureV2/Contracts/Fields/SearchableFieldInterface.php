<?php

namespace Sc\Util\HtmlStructureV2\Contracts\Fields;

interface SearchableFieldInterface
{
    public function getSearchType(): string;

    public function getSearchField(): ?string;
}
