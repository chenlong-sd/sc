<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasSpan
{
    protected int $span = 24;

    public function span(int $span): static
    {
        $this->span = max(1, min(24, $span));

        return $this;
    }

    public function getSpan(): int
    {
        return $this->span;
    }
}
