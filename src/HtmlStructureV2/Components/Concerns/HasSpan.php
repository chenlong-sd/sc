<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasSpan
{
    protected int $span = 24;

    /**
     * 设置当前节点占用的 24 栅格宽度，常用于控制区块横向布局。
     */
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
