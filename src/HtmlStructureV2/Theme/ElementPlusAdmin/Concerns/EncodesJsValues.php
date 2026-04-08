<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns;

use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\JsValueEncoder;

trait EncodesJsValues
{
    protected function jsString(string $value): string
    {
        return JsValueEncoder::encodeString($value);
    }

    protected function jsValue(mixed $value): string
    {
        return JsValueEncoder::encode($value);
    }

    protected function jsAttributeValue(mixed $value): string
    {
        return JsValueEncoder::encodeAttributeValue($value);
    }

    /**
     * 构造一个基于 JS 字面量的三元表达式。
     * 适合 `condition ? 'primary' : ''` 这类需要输出字符串/布尔/null 字面量的动态属性场景，
     * 避免调用侧手写双引号字符串后再被 HTML 属性编码成 `&quot;...&quot;`。
     */
    protected function jsLiteralTernary(string $condition, mixed $truthy, mixed $falsy): string
    {
        return sprintf(
            '%s ? %s : %s',
            trim($condition),
            $this->jsValue($truthy),
            $this->jsValue($falsy)
        );
    }

    /**
     * 规范化最终写入 HTML 的组件属性值。
     * ":" 开头属性的非字符串值会自动转成 JS 字面量，字符串仍视为原始表达式。
     */
    protected function normalizeRenderableAttributeValue(string $attr, mixed $value): string|int|null
    {
        if (str_starts_with($attr, ':')) {
            return is_string($value) ? $value : $this->jsAttributeValue($value);
        }

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        return (string)$value;
    }

    /**
     * 批量规范化组件属性。
     *
     * @param array<string, mixed> $attrs
     * @return array<string, string|int|null>
     */
    protected function normalizeRenderableAttributes(array $attrs): array
    {
        $normalized = [];

        foreach ($attrs as $attr => $value) {
            if (!is_string($attr) || $attr === '') {
                continue;
            }

            $normalized[$attr] = $this->normalizeRenderableAttributeValue($attr, $value);
        }

        return $normalized;
    }
}
