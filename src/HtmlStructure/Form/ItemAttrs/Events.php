<?php

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlStructure\Html\Js\JsFunc;

/**
 * Class Events
 */
trait Events
{
    protected array $events = [];

    /**
     * 事件
     *
     * @param string        $event
     * @param JsFunc|string $handler
     *
     * @return $this
     */
    public function event(string $event, JsFunc|string $handler): static
    {
        $this->events[$event] = $handler;

        return $this;
    }

    /**
     * event 别名
     * @param string        $event
     * @param JsFunc|string $handler
     *
     * @return $this
     */
    public function on(string $event, JsFunc|string $handler): static
    {
        return $this->event($event, $handler);
    }
}