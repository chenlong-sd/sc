<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;

/**
 * Class JsTimer
 */
class JsTimer
{

    private string|\Stringable $code;

    public function __construct(private readonly string $name, private readonly string $type, private readonly int $time,)
    {
    }

    public static function setInterval(string $name, int $time): JsTimer
    {
        return new self($name, 'setInterval', $time,);
    }

    public static function setTimeout(string $name, int $time): JsTimer
    {
        return new self($name, 'setTimeout', $time,);
    }

    public function call(#[Language("JavaScript")]string|\Stringable ...$code): static
    {
        $this->code = JsCode::make(...$code);

        return $this;
    }

    public function toCode(): JsVar
    {
        return JsVar::def($this->name, JsFunc::call($this->type, JsFunc::arrow()->code($this->code), $this->time));
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}