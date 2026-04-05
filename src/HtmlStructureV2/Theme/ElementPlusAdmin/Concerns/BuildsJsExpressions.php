<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns;

trait BuildsJsExpressions
{
    protected function jsModelAccessor(string $root, string $path): string
    {
        $expression = $root;
        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $segment)) {
                $expression .= '.' . $segment;
                continue;
            }

            $expression .= '[' . $this->jsLiteral($segment) . ']';
        }

        return $expression;
    }

    protected function jsReadableAccessor(string $root, string $path): string
    {
        $expression = $root;
        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $segment)) {
                $expression .= '?.' . $segment;
                continue;
            }

            $expression .= '?.[' . $this->jsLiteral($segment) . ']';
        }

        return $expression;
    }

    protected function normalizeFieldExpression(?\Stringable $expression, string $modelName): ?string
    {
        if ($expression === null) {
            return null;
        }

        $raw = trim((string)$expression);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/(?<![\w$])model(?![\w$])/', $modelName, $raw);

        return $normalized ?: $raw;
    }

    protected function jsLiteral(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return match (true) {
            is_int($value), is_float($value) => (string)$value,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => "'" . str_replace(
                ['\\', '\''],
                ['\\\\', '\\\''],
                (string)$value
            ) . "'",
        };
    }
}
