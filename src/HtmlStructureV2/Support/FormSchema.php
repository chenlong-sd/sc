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
}
