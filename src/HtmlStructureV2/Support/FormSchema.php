<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class FormSchema
{
    /**
     * @param FormFieldSchema[] $fields
     * @param string[] $remoteOptionPaths
     * @param string[] $uploadPaths
     * @param FormArrayGroupSchema[] $arrayGroups
     */
    public function __construct(
        private readonly array $fields = [],
        private readonly array $defaults = [],
        private readonly array $pickerDefaults = [],
        private readonly array $rules = [],
        private readonly array $remoteOptions = [],
        private readonly array $selectOptions = [],
        private readonly array $pickers = [],
        private readonly array $linkages = [],
        private readonly array $uploads = [],
        private readonly array $pickerPaths = [],
        private readonly array $remoteOptionPaths = [],
        private readonly array $uploadPaths = [],
        private readonly array $arrayGroups = [],
    ) {
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function fieldPaths(): array
    {
        return array_map(
            static fn(FormFieldSchema $fieldSchema) => $fieldSchema->path(),
            $this->fields
        );
    }

    public function defaults(): array
    {
        return $this->defaults;
    }

    public function initializeData(array $data): array
    {
        return $this->initializeNodeData($this->defaults, $data, $this->arrayGroups);
    }

    public function pickerDefaults(): array
    {
        return $this->pickerDefaults;
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function remoteOptions(): array
    {
        return $this->remoteOptions;
    }

    public function selectOptions(): array
    {
        return $this->selectOptions;
    }

    public function pickers(): array
    {
        return $this->pickers;
    }

    public function linkages(): array
    {
        return $this->linkages;
    }

    public function uploads(): array
    {
        return $this->uploads;
    }

    public function remoteOptionPaths(): array
    {
        return $this->remoteOptionPaths;
    }

    public function pickerPaths(): array
    {
        return $this->pickerPaths;
    }

    public function uploadPaths(): array
    {
        return $this->uploadPaths;
    }

    public function arrayGroups(): array
    {
        return $this->arrayGroups;
    }

    /**
     * @param FormArrayGroupSchema[] $arrayGroups
     */
    private function initializeNodeData(array $defaults, mixed $data, array $arrayGroups, ?string $prefix = null): array
    {
        $source = is_array($data) ? $data : [];
        $output = $defaults;

        foreach ($output as $key => $defaultValue) {
            if (!array_key_exists($key, $source)) {
                continue;
            }

            $value = $source[$key];
            $currentPath = FormPath::resolve($prefix, (string) $key);
            $arrayGroup = $this->findArrayGroupByPath($arrayGroups, $currentPath);
            if ($arrayGroup !== null) {
                $output[$key] = $this->initializeArrayGroupRows($arrayGroup, $value);
                continue;
            }

            if ($this->isAssociativeArray($defaultValue)) {
                $output[$key] = $this->initializeNodeData(
                    $defaultValue,
                    $value,
                    $arrayGroups,
                    $currentPath
                );
                continue;
            }

            if (is_array($defaultValue)) {
                $output[$key] = is_array($value) ? $value : $defaultValue;
                continue;
            }

            $output[$key] = $value;
        }

        return $output;
    }

    private function initializeArrayGroupRows(FormArrayGroupSchema $groupSchema, mixed $rows): array
    {
        if (!is_array($rows) || $this->isAssociativeArray($rows)) {
            return $groupSchema->initialRows();
        }

        return array_values(array_map(
            fn(mixed $row) => $groupSchema->rowSchema()->initializeData(is_array($row) ? $row : []),
            $rows
        ));
    }

    /**
     * @param FormArrayGroupSchema[] $arrayGroups
     */
    private function findArrayGroupByPath(array $arrayGroups, string $path): ?FormArrayGroupSchema
    {
        $path = FormPath::normalize($path);
        foreach ($arrayGroups as $groupSchema) {
            if ($groupSchema->path() === $path) {
                return $groupSchema;
            }
        }

        return null;
    }

    private function isAssociativeArray(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return !array_is_list($value);
    }
}
