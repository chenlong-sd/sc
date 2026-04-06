<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasEvents
{
    /** @var array<string, array<int, JsExpression|StructuredEventInterface>> */
    protected array $events = [];

    /**
     * 为当前组件绑定一个事件处理器。
     * 使用前可先调用 supportedEvents() 查看当前组件约定事件；轻量块/布局类若返回 `*`，
     * 表示事件会直接绑定到其渲染根节点，可使用该节点支持的 DOM / Element Plus 事件名。
     *
     * handler 若为 JS 表达式，运行时统一只接收一个 context 对象，
     * 推荐写法：`(context) => {}` 或 `({ vm }) => {}`；
     * 不支持 `function (a, b, c) {}` 这类按位置参数取值的写法。
     */
    public function on(string $event, string|JsExpression|StructuredEventInterface $handler): static
    {
        $event = ltrim(trim($event), '@');
        if ($event === '') {
            return $this;
        }

        $this->events[$event] ??= [];
        $this->events[$event][] = is_string($handler)
            ? JsExpression::make($handler)
            : $handler;

        return $this;
    }

    /**
     * 批量绑定事件处理器，适合一次性注入多个事件回调。
     * 事件名范围同 on()，可通过 supportedEvents() 先查看约定事件。
     * 每个 handler 的调用方式也与 on() 一致，统一只接收一个 context 对象。
     */
    public function events(array $events): static
    {
        foreach ($events as $event => $handlers) {
            if (!is_string($event)) {
                continue;
            }

            $handlers = is_array($handlers) ? $handlers : [$handlers];
            foreach ($handlers as $handler) {
                if (
                    is_string($handler)
                    || $handler instanceof JsExpression
                    || $handler instanceof StructuredEventInterface
                ) {
                    $this->on($event, $handler);
                }
            }
        }

        return $this;
    }

    /**
     * 返回当前组件支持的事件说明。
     *
     * @return array<string, string>
     */
    public function supportedEvents(): array
    {
        if (method_exists($this, 'defineSupportedEvents')) {
            $events = $this->defineSupportedEvents();
            if (is_array($events) && $events !== []) {
                return $events;
            }
        }

        return [
            '*' => '直接绑定到组件渲染根节点，可使用该节点支持的 DOM / Element Plus 事件，例如 click / change / mouseenter / mouseleave。',
        ];
    }

    public function getEventHandlers(?string $event = null): array
    {
        if ($event === null) {
            return $this->events;
        }

        return $this->events[$event] ?? [];
    }

    public function getFirstEventHandler(string $event): JsExpression|StructuredEventInterface|null
    {
        $handlers = $this->getEventHandlers($event);

        return $handlers[0] ?? null;
    }

    public function hasEventHandlers(?string $event = null): bool
    {
        if ($event === null) {
            return $this->events !== [];
        }

        return ($this->events[$event] ?? []) !== [];
    }
}
