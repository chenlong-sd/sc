<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasElementEvents
{
    use HasEvents {
        on as private bindElementEventHandler;
    }

    /**
     * 为当前轻量组件根节点绑定事件处理器。
     * 常用事件可写 click、change、mouseenter、mouseleave 等；
     * 具体以该组件最终渲染的 DOM / Element Plus 根节点支持的事件为准。
     *
     * handler 签名统一为 `(context) => {}`，
     * 也可直接解构成 `({ event, vm, model }) => {}`；
     * 不按多个位置参数传值。
     *
     * 默认上下文：
     * - event: 当前原生 / 组件事件对象
     * - vm: 当前页面 Vue 实例
     *
     * 额外上下文：
     * - 在 Forms::custom(...) 中通常还会注入 model
     * - 其他包装场景可能额外注入 row / tableKey / listKey 等运行时字段
     */
    public function on(string $event, string|JsExpression|StructuredEventInterface $handler): static
    {
        return $this->bindElementEventHandler($event, $handler);
    }
}
