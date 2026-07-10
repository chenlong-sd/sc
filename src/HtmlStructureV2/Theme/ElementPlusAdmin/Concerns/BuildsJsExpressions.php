<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns;

trait BuildsJsExpressions
{
    protected function buildFieldExpression(
        ?\Stringable $expression,
        string $modelName,
        ?string $formExpression = null,
        ?string $scopeExpression = null,
        ?string $fieldPathExpression = null,
        array $fieldMeta = []
    ): ?string
    {
        if ($expression === null) {
            return null;
        }

        $raw = trim((string)$expression);
        if ($raw === '') {
            return null;
        }

        $modelExpression = trim($modelName) === '' ? '{}' : trim($modelName);
        $resolvedFormExpression = trim((string)$formExpression) === '' ? $modelExpression : trim((string)$formExpression);
        $resolvedScopeExpression = trim((string)$scopeExpression) === '' ? 'null' : trim((string)$scopeExpression);
        $resolvedFieldPathExpression = trim((string)$fieldPathExpression) === '' ? 'null' : trim((string)$fieldPathExpression);
        $fieldLiteral = $this->jsValue($fieldMeta);

        return sprintf(
            '(() => { const __scVm = typeof $root !== "undefined" ? $root : (globalThis.__SC_V2_PAGE__?.vm ?? null); const __scModel = %s; const __scForm = %s; const __scState = typeof getState === "function" ? getState() : (__scVm?.pageState || {}); const __scScope = %s; const __scFieldName = %s; const __scOptions = typeof getFieldOptions === "function" ? (getFieldOptions(__scScope, __scFieldName) ?? []) : []; const __scFieldConfig = typeof getFieldConfig === "function" ? (getFieldConfig(__scScope, __scFieldName) ?? {}) : {}; const __scOptionLoading = typeof getFieldOptionLoading === "function" ? getFieldOptionLoading(__scScope, __scFieldName) : false; const __scOptionLoaded = typeof getFieldOptionLoaded === "function" ? getFieldOptionLoaded(__scScope, __scFieldName) : false; const __scField = %s; const model = __scModel; const form = __scForm; const state = __scState; const pageState = __scState; const scope = __scScope; const fieldName = __scFieldName; const vm = __scVm; const options = Array.isArray(__scOptions) ? __scOptions : []; const fieldConfig = __scFieldConfig && typeof __scFieldConfig === "object" ? __scFieldConfig : {}; const optionLoading = __scOptionLoading === true; const optionLoaded = __scOptionLoaded === true; const field = __scField; const props = (field && typeof field === "object" && field.props && typeof field.props === "object") ? field.props : {}; return (%s); })()',
            $modelExpression,
            $resolvedFormExpression,
            $resolvedScopeExpression,
            $resolvedFieldPathExpression,
            $fieldLiteral,
            $raw
        );
    }

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
        return $this->buildFieldExpression($expression, $modelName);
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
