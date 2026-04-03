<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class JsonExpressionEncoder
{
    private const PLACEHOLDER = '__SC_V2_EXPR__';

    public static function encode(mixed $data): string
    {
        return self::encodeInternal($data, true);
    }

    public static function encodeCompact(mixed $data): string
    {
        return self::encodeInternal($data, false);
    }

    private static function encodeInternal(mixed $data, bool $pretty): string
    {
        $expressions = [];
        $normalized = self::normalize($data, $expressions);
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($normalized, $flags);

        return preg_replace_callback(
            '/"' . self::PLACEHOLDER . '(\d+)' . self::PLACEHOLDER . '"/',
            fn(array $match) => $expressions[(int)$match[1]] ?? 'null',
            $json ?: 'null'
        );
    }

    private static function normalize(mixed $data, array &$expressions): mixed
    {
        if ($data instanceof JsExpression) {
            $index = count($expressions);
            $expressions[$index] = $data->expression();

            return self::PLACEHOLDER . $index . self::PLACEHOLDER;
        }

        if ($data instanceof \BackedEnum) {
            return $data->value;
        }

        if ($data instanceof \JsonSerializable) {
            return self::normalize($data->jsonSerialize(), $expressions);
        }

        if (is_array($data)) {
            $normalized = [];
            foreach ($data as $key => $value) {
                $normalized[$key] = self::normalize($value, $expressions);
            }

            return $normalized;
        }

        if ($data instanceof \Stringable) {
            return (string)$data;
        }

        return $data;
    }
}
