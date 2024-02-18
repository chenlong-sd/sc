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
    /**
     * @var array|JsIf[]
     */
    private array $elseIfCode = [];

    private bool $isElseIfStatus = false;

    public function __construct(private readonly string $where)
    {
    }

    public static function when(#[Language("JavaScript")] string $where): JsIf
    {
        return new self($where);
    }

    public function then(#[Language("JavaScript")] string ...$code): static
    {
        if ($this->isElseIfStatus) {
            end($this->elseIfCode)->then(...$code);
            $this->isElseIfStatus = false;
        }else{
            $this->thenCode = JsCode::make(...$code);
        }

        return $this;
    }

    public function else(#[Language("JavaScript")] string ...$code): static
    {
        $this->elseCode = JsCode::make(...$code);

        return $this;
    }

    public function elseIf(#[Language("JavaScript")] string $where): static
    {
        $this->elseIfCode[]   = JsIf::when($where);
        $this->isElseIfStatus = true;

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

        if ($this->elseIfCode) {
            $code .= implode(array_map(fn($if) => 'else ' . trim($if->toCode()), $this->elseIfCode));
        }

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