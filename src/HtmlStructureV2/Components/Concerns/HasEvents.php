<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasEvents
{
    /** @var array<string, JsExpression[]> */
    protected array $events = [];

    public function on(string $event, string|JsExpression $handler): static
    {
        $event = trim($event);
        if ($event === '') {
            return $this;
        }

        $this->events[$event] ??= [];
        $this->events[$event][] = $handler instanceof JsExpression
            ? $handler
            : JsExpression::make($handler);

        return $this;
    }

    public function events(array $events): static
    {
        foreach ($events as $event => $handlers) {
            if (!is_string($event)) {
                continue;
            }

            $handlers = is_array($handlers) ? $handlers : [$handlers];
            foreach ($handlers as $handler) {
                if (is_string($handler) || $handler instanceof JsExpression) {
                    $this->on($event, $handler);
                }
            }
        }

        return $this;
    }

    public function getEventHandlers(?string $event = null): array
    {
        if ($event === null) {
            return $this->events;
        }

        return $this->events[$event] ?? [];
    }

    public function getFirstEventHandler(string $event): ?JsExpression
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
