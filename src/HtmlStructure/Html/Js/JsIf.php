<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;

/**
 * Class JsIf
 */
class JsIf
{
    private string $thenCode = '';
    private string $elseCode = '';

    public function __construct(private readonly string $where)
    {
    }

    public static function when(#[Language("JavaScript")] string $where): JsIf
    {
        return new self($where);
    }

    public function then(#[Language("JavaScript")] string ...$code): static
    {
        $this->thenCode = JsCode::make(...$code);

        return $this;
    }

    public function else(#[Language("JavaScript")] string ...$code): static
    {
        $this->elseCode = JsCode::make(...$code);

        return $this;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    public function toCode(): string
    {
        $code = <<<JS
            if($this->where){
                {$this->thenCode}
            }
        JS;

        if ($this->elseCode){
            $code = rtrim($code) . ltrim(<<<JS
                else{
                    {$this->elseCode}
                }
            JS);
        }

        return $code;
    }
}