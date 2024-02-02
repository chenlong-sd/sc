<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;

/**
 * Class JsFor
 */
class JsFor
{
    /**
     * @var string
     */
    private string $code = '';

    public function __construct(private readonly string $expression)
    {
    }

    public static function loop(#[Language('JavaScript')] string $expression): JsFor
    {
        return new self($expression);
    }

    public function then(#[Language('JavaScript')] string ...$code): static
    {
        $this->code = JsCode::make(...$code);

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