<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Fields\DateField;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class ListFilterSearchSchemaBuilder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function build(Form $form, array $fallbackSchema = []): array
    {
        $schema = [];
        $resolvedPaths = [];
        $disabledPaths = [];

        foreach ($form->schema()->fields() as $fieldSchema) {
            $path = trim($fieldSchema->path());
            if ($path === '') {
                continue;
            }

            $field = $fieldSchema->field();
            $resolvedPaths[$path] = true;
            if ($field->hasSearchConfig() && !$field->isSearchEnabled()) {
                $disabledPaths[$path] = true;
                continue;
            }

            $schema[$path] = $this->buildSchemaItem(
                $field,
                is_array($fallbackSchema[$path] ?? null) ? $fallbackSchema[$path] : []
            );
        }

        foreach ($fallbackSchema as $path => $item) {
            if (
                !is_string($path)
                || $path === ''
                || isset($resolvedPaths[$path])
                || isset($disabledPaths[$path])
                || !is_array($item)
            ) {
                continue;
            }

            $schema[$path] = $item;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemaItem(Field $field, array $fallbackItem = []): array
    {
        $searchConfig = $field->getSearchConfig() ?? [];
        $item = $fallbackItem;

        if (!isset($item['type']) || !is_string($item['type']) || trim((string)$item['type']) === '') {
            $item['type'] = $this->resolveSearchType($field);
        }

        $explicitType = is_string($searchConfig['type'] ?? null) ? trim((string)$searchConfig['type']) : '';
        if ($explicitType !== '') {
            $item['type'] = strtoupper($explicitType);
        }

        $targetField = is_string($searchConfig['field'] ?? null) ? trim((string)$searchConfig['field']) : '';
        if ($targetField !== '') {
            $item['field'] = $targetField;
        }

        if ($field->type() === FieldType::HIDDEN) {
            $item['hidden'] = true;
        }

        return $item;
    }

    private function resolveSearchType(Field $field): string
    {
        if ($field instanceof DateField && $field->isRangePicker()) {
            return 'BETWEEN';
        }

        return match ($field->type()) {
            FieldType::DATE_RANGE => 'BETWEEN',
            FieldType::CHECKBOX => 'IN',
            FieldType::SELECT => $this->isMultipleField($field) ? 'IN' : '=',
            default => '=',
        };
    }

    private function isMultipleField(Field $field): bool
    {
        $multiple = $field->getProps()['multiple'] ?? null;

        return $multiple !== null && $multiple !== false && $multiple !== 'false';
    }
}
