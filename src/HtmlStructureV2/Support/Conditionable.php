<?php

namespace Sc\Util\HtmlStructureV2\Support;

trait Conditionable
{
    /**
     * 条件成立时执行链式配置回调；不成立时可选执行兜底回调。
     *
     * 回调统一接收当前组件实例本身，返回值会被忽略，方法始终继续返回当前实例。
     */
    public function when(bool $condition, callable $callback, ?callable $otherwise = null): static
    {
        if ($condition) {
            $callback($this);

            return $this;
        }

        if ($otherwise !== null) {
            $otherwise($this);
        }

        return $this;
    }
}
