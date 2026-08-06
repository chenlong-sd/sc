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
    private const DYNAMIC_SEARCH_VALUE_KEY = '__scDynamicSearch';

    public function build(string $listKey, Table $table): ?Form
    {
        $fields = [];
        $resolvedNames = [];
        $searchSchema = $table->getSearchSchema();

        foreach ($table->columns() as $column) {
            if (!$column->isSearchable()) {
                continue;
            }

            $searchName = $column->getSearchName();
            $field = $this->buildField(
                $searchName,
                $column->label(),
                $searchSchema[$searchName] ?? $column->getSearchConfig() ?? [],
                $column->getDisplay()
            );
            if ($field === null) {
                continue;
            }

            $fields[] = $field;
            $resolvedNames[$searchName] = true;
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

        if (($searchConfig['hidden'] ?? false) === true) {
            return null;
        }

        $searchType = strtoupper((string)($searchConfig['type'] ?? '='));
        $searchField = is_string($searchConfig['field'] ?? null) && $searchConfig['field'] !== ''
            ? (string)$searchConfig['field']
            : $name;

        $optionField = $this->buildOptionField(
            $name,
            $label,
            $searchType,
            $searchField,
            $display
        );
        if ($optionField !== null) {
            return $optionField;
        }

        $rangeField = $this->buildRangeField($name, $label, $searchType, $display, $searchField);
        if ($rangeField !== null) {
            return $rangeField;
        }

        if ($searchType === 'IN') {
            return Fields::select($name)
                ->placeholder($label)
                ->default([])
                ->prop('multiple', '')
                ->prop('filterable', '')
                ->prop('allow-create', '')
                ->prop('default-first-option', '')
                ->prop('collapse-tags', '');
        }

        return Fields::text($name)
            ->placeholder($label);
    }

    private function buildOptionField(
        string $name,
        string $label,
        string $searchType,
        string $searchField,
        ?array $display
    ): ?Field {
        if ($display === null) {
            return null;
        }

        $options = [];
        $usesDynamicSearchValues = false;
        $isDynamicDisplay = ($display['type'] ?? '') === 'dynamic';
        foreach ($this->displayCandidates($display) as $candidate) {
            $candidateOptions = match ($candidate['type'] ?? '') {
                'mapping', 'tag', 'switch' => $this->normalizeDisplayOptions($candidate['options'] ?? []),
                'boolean', 'boolean_tag' => [
                    ['value' => 1, 'label' => (string)($candidate['truthyLabel'] ?? '是')],
                    ['value' => 0, 'label' => (string)($candidate['falsyLabel'] ?? '否')],
                ],
                default => [],
            };

            if ($isDynamicDisplay) {
                $candidateSearch = is_array($candidate['_search'] ?? null)
                    ? $candidate['_search']
                    : [];
                $candidateValuePath = is_string($candidate['valuePath'] ?? null)
                    && trim((string)$candidate['valuePath']) !== ''
                    ? trim((string)$candidate['valuePath'])
                    : $name;
                $candidateSearchField = is_string($candidateSearch['field'] ?? null)
                    && trim((string)$candidateSearch['field']) !== ''
                    ? trim((string)$candidateSearch['field'])
                    : ($candidateValuePath !== $name ? $candidateValuePath : $searchField);
                $candidateSearchType = is_string($candidateSearch['type'] ?? null)
                    && trim((string)$candidateSearch['type']) !== ''
                    ? strtoupper(trim((string)$candidateSearch['type']))
                    : $searchType;
                $usesCandidateSearch = $candidateSearchField !== $searchField
                    || $candidateSearchType !== $searchType
                    || $candidateValuePath !== $name;
            } else {
                $candidateValuePath = $name;
                $candidateSearchField = $searchField;
                $candidateSearchType = $searchType;
                $usesCandidateSearch = false;
            }

            foreach ($candidateOptions as $option) {
                if ($usesCandidateSearch) {
                    $option = $this->withDynamicSearchValue(
                        $option,
                        $candidateSearchField,
                        $candidateSearchType,
                        $candidateValuePath
                    );
                    $usesDynamicSearchValues = true;
                }

                $key = serialize($option['value'] ?? null);
                $options[$key] ??= $option;
            }
        }
        $options = array_values($options);

        if ($options === []) {
            return null;
        }

        $field = Fields::select($name)
            ->placeholder($label)
            ->options($options);
        if ($usesDynamicSearchValues) {
            $field->prop('value-key', self::DYNAMIC_SEARCH_VALUE_KEY . '.key');
        }
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
        ?array $display,
        ?string $searchField = null
    ): ?Field {
        if ($searchType !== 'BETWEEN') {
            return null;
        }

        if ($this->looksLikeDatetime($name, $display, $searchField)) {
            return Fields::daterange($name)
                ->pickerType('datetimerange')
                ->format('YYYY-MM-DD HH:mm:ss')
                ->valueFormat('YYYY-MM-DD HH:mm:ss')
                ->prop('start-placeholder', '开始' . $label)
                ->prop('end-placeholder', '结束' . $label);
        }

        if ($this->looksLikeDate($name, $display, $searchField)) {
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

    /**
     * @param array{value: mixed, label: string} $option
     * @return array{value: array<string, array<string, mixed>>, label: string, key: string}
     */
    private function withDynamicSearchValue(
        array $option,
        string $field,
        string $type,
        string $rowPath
    ): array {
        $meta = [
            'value' => $option['value'] ?? null,
            'field' => $field,
            'type' => $type,
            'rowPath' => $rowPath,
        ];
        $meta['key'] = hash('sha256', serialize($meta));

        return [
            'value' => [self::DYNAMIC_SEARCH_VALUE_KEY => $meta],
            'label' => (string)($option['label'] ?? ''),
            'key' => self::DYNAMIC_SEARCH_VALUE_KEY . ':' . $meta['key'],
        ];
    }

    private function looksLikeDatetime(string $name, ?array $display, ?string $searchField = null): bool
    {
        $candidate = strtolower($searchField ?: $name);

        if (str_contains($candidate, 'time')) {
            return true;
        }

        foreach ($this->displayCandidates($display) as $candidateDisplay) {
            if (($candidateDisplay['type'] ?? '') !== 'datetime') {
                continue;
            }

            $format = (string)($candidateDisplay['format'] ?? '');
            if (preg_match('/H|h|mm|ss/', $format) === 1) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeDate(string $name, ?array $display, ?string $searchField = null): bool
    {
        $candidate = strtolower($searchField ?: $name);

        if (str_contains($candidate, 'date')) {
            return true;
        }

        foreach ($this->displayCandidates($display) as $candidateDisplay) {
            if (($candidateDisplay['type'] ?? '') === 'datetime') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function displayCandidates(?array $display): array
    {
        if ($display === null) {
            return [];
        }
        if (($display['type'] ?? '') !== 'dynamic') {
            return [$display];
        }

        $candidates = [];
        foreach ($display['branches'] ?? [] as $branch) {
            if (is_array($branch) && is_array($branch['display'] ?? null)) {
                $candidate = $branch['display'];
                if (is_array($branch['search'] ?? null)) {
                    $candidate['_search'] = $branch['search'];
                }
                $candidates[] = $candidate;
            }
        }
        if (is_array($display['default'] ?? null)) {
            $candidate = $display['default'];
            if (is_array($display['defaultSearch'] ?? null)) {
                $candidate['_search'] = $display['defaultSearch'];
            }
            $candidates[] = $candidate;
        }

        return $candidates;
    }
}
