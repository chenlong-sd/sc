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

    public function __construct(private readonly string $where)
    {
    }

    public static function loop(#[Language('JavaScript')] string $where): JsFor
    {
        return new self($where);
    }

    public function then(#[Language('JavaScript')] string ...$code): static
    {
        $this->code = JsCode::make(...$code);

        return $this;
    }

    public function toCode(): string
    {
        return <<<JS
            for ($this->where){
                $this->code
            }
        JS;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}