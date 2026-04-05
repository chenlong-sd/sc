<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasEvents
{
    /** @var array<string, array<int, JsExpression|StructuredEventInterface>> */
    protected array $events = [];

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
