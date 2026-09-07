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
        if ($json === false) {
            return 'null';
        }

        // 第一步只替换真正的表达式标记：前面不允许有反斜杠，
        // 避免把被转义的、恰好含标记模式的普通字符串当成表达式（见 normalize 的字符串分支）。
        $json = preg_replace_callback(
            '/"(?<!\\\\)' . self::PLACEHOLDER . '(\d+)' . self::PLACEHOLDER . '"/',
            fn(array $match) => $expressions[(int)$match[1]] ?? 'null',
            $json
        );

        // 第二步还原被转义的普通字符串：normalize 给标记模式插入的 `\` 经 json_encode 后落成 `\\`（连续两个反斜杠），
        // 去掉它们使原文原样回到配置里。
        return preg_replace(
            '/\\\\\\\\' . self::PLACEHOLDER . '(\d+)' . self::PLACEHOLDER . '/',
            self::PLACEHOLDER . '$1' . self::PLACEHOLDER,
            $json
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

        // 字符串值若恰好包含标记模式，先转义（前插 `\`），避免编码后与表达式标记混淆；
        // encodeInternal 第二步会还原。
        if (is_string($data)) {
            return (string) preg_replace(
                '/' . self::PLACEHOLDER . '\d+' . self::PLACEHOLDER . '/',
                '\\\\$0',
                $data
            );
        }

        return $data;
    }
}
