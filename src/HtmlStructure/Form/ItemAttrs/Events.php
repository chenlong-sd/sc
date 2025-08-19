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
     * event 别名, change 事件的参数默认为当前value,
     * <br>如果需要获取当前循环的对象所有值，可以调用 setOptionsAttrs(':value', 'item')，
     * 设置value的值为 item,如歌需要传输其他参数，可使用 (item) => AChange(1, item) 方式传输额外的参数
     * <br>也可以通过 (v) => AChange(v, optionsVarName.find(i => i.value === v)) 方式传输整个对象
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