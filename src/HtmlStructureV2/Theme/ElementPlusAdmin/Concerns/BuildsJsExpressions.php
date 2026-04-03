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

    protected function jsBlankCheck(string $expression): string
    {
        return sprintf(
            "(%s === '' || %s === null || %s === undefined || (Array.isArray(%s) && %s.length === 0))",
            $expression,
            $expression,
            $expression,
            $expression,
            $expression
        );
    }

    protected function jsTruthyValueCheck(string $expression): string
    {
        return sprintf(
            "([true, 1, '1', 'true', 'yes', 'on'].includes(%s) || ['true', 'yes', 'on', '1'].includes(String(%s).toLowerCase()))",
            $expression,
            $expression
        );
    }

    protected function jsFalsyValueCheck(string $expression): string
    {
        return sprintf(
            "([false, 0, '0', 'false', 'no', 'off'].includes(%s) || ['false', 'no', 'off', '0'].includes(String(%s).toLowerCase()))",
            $expression,
            $expression
        );
    }

    protected function jsDateFormatExpression(string $valueExpression, string $format): string
    {
        $formatLiteral = $this->jsLiteral($format);

        return <<<JS
(() => {
  const value = {$valueExpression};
  if (value === '' || value === null || value === undefined) return '';

  const raw = String(value).trim();
  const isNumeric = typeof value === 'number' || /^-?\d+(\.\d+)?$/.test(raw);
  const normalizedNumber = isNumeric ? Number(value) : NaN;
  const timestamp = isNumeric
    ? (String(Math.trunc(Math.abs(normalizedNumber))).length <= 10 ? normalizedNumber * 1000 : normalizedNumber)
    : NaN;
  const date = isNumeric
    ? new Date(timestamp)
    : new Date(raw.replace('T', ' ').replace(/-/g, '/'));

  if (Number.isNaN(date.getTime())) return raw;

  const pad = (num) => String(num).padStart(2, '0');
  return {$formatLiteral}
    .replace(/YYYY/g, String(date.getFullYear()))
    .replace(/MM/g, pad(date.getMonth() + 1))
    .replace(/DD/g, pad(date.getDate()))
    .replace(/HH/g, pad(date.getHours()))
    .replace(/mm/g, pad(date.getMinutes()))
    .replace(/ss/g, pad(date.getSeconds()));
})()
JS;
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
