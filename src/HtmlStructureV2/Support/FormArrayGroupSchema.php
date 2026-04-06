<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;

final class FormArrayGroupSchema
{
    public function __construct(
        private readonly string $path,
        private readonly FormArrayGroup $group,
        private readonly FormSchema $rowSchema,
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function segments(): array
    {
        return FormPath::segments($this->path);
    }

    public function group(): FormArrayGroup
    {
        return $this->group;
    }

    public function rowSchema(): FormSchema
    {
        return $this->rowSchema;
    }

    public function type(): string
    {
        return $this->group->runtimeType();
    }

    public function minRows(): int
    {
        return $this->group->getMinRows();
    }

    public function maxRows(): ?int
    {
        return $this->group->getMaxRows();
    }

    public function defaultRow(): array
    {
        return $this->rowSchema->defaults();
    }

    public function initialRows(): array
    {
        return $this->group->buildInitialRows($this->defaultRow());
    }

    /**
     * @return FormArrayGroupSchema[]
     */
    public function rowArrayGroups(): array
    {
        return $this->rowSchema()->arrayGroups();
    }

    public function toRuntimeConfig(): array
    {
        return [
            'path' => $this->path(),
            'type' => $this->type(),
            'minRows' => $this->minRows(),
            'maxRows' => $this->maxRows(),
            'defaultRow' => $this->defaultRow(),
            'initialRows' => $this->initialRows(),
            'defaultPickerRow' => $this->rowSchema()->pickerDefaults(),
            'rowRemoteOptions' => $this->rowSchema()->remoteOptions(),
            'rowRemoteOptionPaths' => $this->rowSchema()->remoteOptionPaths(),
            'rowSelectOptions' => $this->rowSchema()->selectOptions(),
            'rowPickers' => $this->rowSchema()->pickers(),
            'rowPickerPaths' => $this->rowSchema()->pickerPaths(),
            'rowLinkages' => $this->rowSchema()->linkages(),
            'rowUploads' => $this->rowSchema()->uploads(),
            'rowUploadPaths' => $this->rowSchema()->uploadPaths(),
            'rowArrayGroups' => array_map(
                static fn(self $groupSchema) => $groupSchema->toRuntimeConfig(),
                $this->rowArrayGroups()
            ),
        ];
    }
}
