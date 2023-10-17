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
     * @param string $event
     * @param JsFunc $handler
     *
     * @return $this
     */
    public function event(string $event, JsFunc $handler): static
    {
        $this->events[$event] = $handler;

        return $this;
    }
}