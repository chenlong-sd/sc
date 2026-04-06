<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;

final class ListAutoFilterFormFactory
{
    public function build(string $listKey, Table $table): ?Form
    {
        $fields = [];
        $resolvedNames = [];
        $searchSchema = $table->getSearchSchema();

        foreach ($table->columns() as $column) {
            if (!$column->isSearchable()) {
                continue;
            }

            $field = $this->buildField(
                $column->prop(),
                $column->label(),
                $searchSchema[$column->prop()] ?? $column->getSearchConfig() ?? [],
                $column->getDisplay()
            );
            if ($field === null) {
                continue;
            }

            $fields[] = $field;
            $resolvedNames[$column->prop()] = true;
        }

        foreach ($searchSchema as $name => $config) {
            if (isset($resolvedNames[$name])) {
                continue;
            }

            $field = $this->buildField($name, $name, $config, null);
            if ($field === null) {
                continue;
            }

            $fields[] = $field;
        }

        if ($fields === []) {
            return null;
        }

        return Forms::make($listKey . '-auto-filters')
            ->inline()
            ->addFields(...$fields);
    }

    private function buildField(
        string $name,
        string $label,
        array $searchConfig,
        ?array $display
    ): ?Field {
        if ($name === '') {
            return null;
        }

        $searchType = strtoupper((string)($searchConfig['type'] ?? '='));

        $optionField = $this->buildOptionField($name, $label, $searchType, $display);
        if ($optionField !== null) {
            return $optionField;
        }

        $rangeField = $this->buildRangeField($name, $label, $searchType, $display);
        if ($rangeField !== null) {
            return $rangeField;
        }

        if ($searchType === 'IN') {
            return Fields::select($name)
                ->placeholder('请选择' . $label)
                ->default([])
                ->prop('multiple', '')
                ->prop('filterable', '')
                ->prop('allow-create', '')
                ->prop('default-first-option', '')
                ->prop('collapse-tags', '');
        }

        return Fields::text($name)
            ->placeholder('请输入' . $label);
    }

    private function buildOptionField(
        string $name,
        string $label,
        string $searchType,
        ?array $display
    ): ?Field {
        if ($display === null) {
            return null;
        }

        $options = match ($display['type'] ?? '') {
            'mapping', 'tag' => $this->normalizeDisplayOptions($display['options'] ?? []),
            'boolean', 'boolean_tag' => [
                ['value' => 1, 'label' => (string)($display['truthyLabel'] ?? '是')],
                ['value' => 0, 'label' => (string)($display['falsyLabel'] ?? '否')],
            ],
            default => [],
        };

        if ($options === []) {
            return null;
        }

        $field = Fields::select($name)
            ->placeholder('请选择' . $label)
            ->options($options);
        if ($searchType !== 'IN') {
            return $field;
        }

        return $field
            ->default([])
            ->prop('multiple', '')
            ->prop('collapse-tags', '')
            ->prop('filterable', '');
    }

    private function buildRangeField(
        string $name,
        string $label,
        string $searchType,
        ?array $display
    ): ?Field {
        if ($searchType !== 'BETWEEN') {
            return null;
        }

        if ($this->looksLikeDatetime($name, $display)) {
            return Fields::daterange($name)
                ->prop('type', 'datetimerange')
                ->format('YYYY-MM-DD HH:mm:ss')
                ->valueFormat('YYYY-MM-DD HH:mm:ss')
                ->prop('start-placeholder', '开始' . $label)
                ->prop('end-placeholder', '结束' . $label);
        }

        if ($this->looksLikeDate($name, $display)) {
            return Fields::daterange($name)
                ->prop('start-placeholder', '开始' . $label)
                ->prop('end-placeholder', '结束' . $label);
        }

        return null;
    }

    private function normalizeDisplayOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $index => $option) {
            if (!is_array($option)) {
                $normalized[] = [
                    'value' => $index,
                    'label' => (string)$option,
                ];

                continue;
            }

            $normalized[] = [
                'value' => $option['value'] ?? $index,
                'label' => (string)($option['label'] ?? ($option['value'] ?? $index)),
            ];
        }

        return $normalized;
    }

    private function looksLikeDatetime(string $name, ?array $display): bool
    {
        if (str_contains(strtolower($name), 'time')) {
            return true;
        }

        if (($display['type'] ?? '') !== 'datetime') {
            return false;
        }

        $format = (string)($display['format'] ?? '');

        return preg_match('/H|h|mm|ss/', $format) === 1;
    }

    private function looksLikeDate(string $name, ?array $display): bool
    {
        if (str_contains(strtolower($name), 'date')) {
            return true;
        }

        return ($display['type'] ?? '') === 'datetime';
    }
}
