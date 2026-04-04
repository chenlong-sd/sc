<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait EncodesJsValues
{
    protected function jsString(string $value): string
    {
        return "'" . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            $value
        ) . "'";
    }

    protected function jsValue(mixed $value): string
    {
        if ($value instanceof JsExpression) {
            return $value->expression();
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_array($value)) {
            if ($this->isJsList($value)) {
                return '[' . implode(', ', array_map(fn(mixed $item) => $this->jsValue($item), $value)) . ']';
            }

            return '{' . implode(', ', array_map(
                fn(string|int $key, mixed $item) => $this->jsString((string) $key) . ': ' . $this->jsValue($item),
                array_keys($value),
                $value
            )) . '}';
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        return match (true) {
            is_int($value), is_float($value) => (string) $value,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => $this->jsString((string) $value),
        };
    }

    private function isJsList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
