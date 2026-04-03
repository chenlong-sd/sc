<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class JsExpression
{
    public function __construct(
        private readonly string $expression
    ) {
    }

    public static function make(string $expression): self
    {
        return new self($expression);
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
