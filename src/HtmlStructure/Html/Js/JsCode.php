<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;

/**
 * js 代码块
 *
 * Class CodeBlock
 */
class JsCode
{
    private array $codes = [];

    public function __construct(#[Language('JavaScript')] string|\Stringable $code = '')
    {
        $code and $this->then($code);
    }

    public static function create(#[Language('JavaScript')] string|\Stringable $code): JsCode
    {
        return new self($code);
    }

    public static function make(#[Language('JavaScript')] string ...$code): JsCode
    {
        $block = new self();
        $block->codes = $code;

        return $block;
    }

    public static function if(#[Language('JavaScript')] string $where, #[Language('JavaScript')] string $trueHandle, #[Language('JavaScript')] string|\Stringable $falseHandle = ''): JsCode
    {
        return (new self())->thenIf($where, $trueHandle, $falseHandle);
    }

    public function then(#[Language('JavaScript')] string|\Stringable $code): static
    {
        $this->codes[] = $code;

        return $this;
    }

    public function thenIf(#[Language('JavaScript')] string $where, #[Language('JavaScript')] string $trueHandle, #[Language('JavaScript')] string|\Stringable $falseHandle = ''): static
    {
        if ($falseHandle) {
            $this->codes[] = <<<JS
                if ($where) {
                    $trueHandle;
                }else{
                    $falseHandle;
                }
            JS;
        } else {
            $this->codes[] = <<<JS
                if ($where) {
                    $trueHandle;
                }
            JS;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    public function toCode(): string
    {
        return implode("\r\n", $this->codes);
    }
}