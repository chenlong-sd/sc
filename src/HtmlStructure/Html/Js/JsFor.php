<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlStructure\Html\Js;

/**
 * Class JsFor
 */
class JsFor
{
    /**
     * @var JsCode|null
     */
    private ?JsCode $code;

    public function __construct(private readonly string $expression)
    {
        $this->code = Js::code("// for start");
    }

    public static function loop(#[Language('JavaScript')] string $expression): JsFor
    {
        return new self($expression);
    }

    public function then(#[Language('JavaScript')] string ...$code): static
    {
        $this->code->then(...$code);

        return $this;
    }

    public function toCode(): string
    {
        return <<<JS
            for ($this->expression){
                $this->code
            }
        JS;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}