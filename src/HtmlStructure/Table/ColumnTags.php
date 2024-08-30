<?php

namespace Sc\Util\HtmlStructure\Table;

/**
 * Class ColumnTags
 */
class ColumnTags
{
    private array $tags;

    public function __construct(array $tags = [])
    {
        $this->tags = $tags;
    }

    public function put($value, $tag): void
    {
        $this->tags[$value] = $tag;
    }

    public function get($value): ?string
    {
        return $this->tags[$value] ?? null;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}