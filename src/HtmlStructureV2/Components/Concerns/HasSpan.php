<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasSpan
{
    protected int $span = 24;
    protected int $afterSpan = 0;

    /**
     * 设置当前节点占用的 24 栅格宽度，常用于控制区块横向布局。
     */
    public function span(int $span): static
    {
        $this->span = max(1, min(24, $span));

        return $this;
    }

    /**
     * 设置当前节点后方补齐的空栅格宽度，常用于强制后续节点换到下一行。
     */
    public function afterSpan(int $span): static
    {
        $this->afterSpan = max(0, min(24, $span));

        return $this;
    }

    public function getSpan(): int
    {
        return $this->span;
    }

    public function getAfterSpan(): int
    {
        return $this->afterSpan;
    }
}
