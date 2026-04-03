<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasSearch
{
    protected ?string $searchType = null;
    protected ?string $searchField = null;

    public function searchType(string $searchType): static
    {
        $this->searchType = strtoupper($searchType);

        return $this;
    }

    public function searchField(string $searchField): static
    {
        $this->searchField = $searchField;

        return $this;
    }

    public function getSearchType(): string
    {
        return $this->searchType ?? $this->defaultSearchType();
    }

    public function getSearchField(): ?string
    {
        return $this->searchField;
    }

    protected function defaultSearchType(): string
    {
        return '=';
    }
}
