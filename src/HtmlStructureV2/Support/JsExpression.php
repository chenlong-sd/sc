<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class JsExpression
{
    public function __construct(
        private readonly string $expression
    ) {
    }

    /**
     * 创建一段原样注入前端的 JS 表达式。
     * 不会被当作普通字符串转义，适合事件 handler、动态 payload/query、
     * 字段显隐条件等需要在浏览器端执行的场景。
     */
    public static function make(string $expression): self
    {
        return new self($expression);
    }

    /**
     * 传入字符串时自动包装成 JsExpression；已是 JsExpression 时原样返回。
     */
    public static function ensure(string|self $expression): self
    {
        return $expression instanceof self ? $expression : new self($expression);
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
