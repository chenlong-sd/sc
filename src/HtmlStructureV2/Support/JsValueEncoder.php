<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class JsValueEncoder
{
    public static function encodeString(string $value): string
    {
        return "'" . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            $value
        ) . "'";
    }

    public static function encode(mixed $value): string
    {
        if ($value instanceof JsExpression) {
            return $value->expression();
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof \JsonSerializable) {
            return self::encode($value->jsonSerialize());
        }

        if (is_array($value)) {
            if (self::isList($value)) {
                return '[' . implode(', ', array_map(
                    static fn (mixed $item): string => self::encode($item),
                    $value
                )) . ']';
            }

            return '{' . implode(', ', array_map(
                static fn (string|int $key, mixed $item): string => self::encodeString((string)$key) . ': ' . self::encode($item),
                array_keys($value),
                $value
            )) . '}';
        }

        if ($value instanceof \Stringable) {
            $value = (string)$value;
        }

        return match (true) {
            is_int($value), is_float($value) => (string)$value,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => self::encodeString((string)$value),
        };
    }

    public static function encodeAttributeValue(mixed $value): string
    {
        if ($value instanceof JsExpression) {
            return $value->expression();
        }

        if (is_array($value) || is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return self::encode($value);
        }

        return (string)$value;
    }

    private static function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
