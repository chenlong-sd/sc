<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Support\FormArrayGroupSchema;
use Sc\Util\HtmlStructureV2\Support\FormPath;
use Sc\Util\HtmlStructureV2\Support\FormSchema;

final class FormRenderState
{
    public function __construct(
        public readonly FormScope $scope,
        public readonly string $model,
        public readonly ?array $modelPath,
        public readonly ?string $ref,
        public readonly ?string $rules,
        public readonly ?array $rulesPath,
        public readonly ?string $optionState,
        public readonly ?array $optionStatePath,
        public readonly ?string $optionLoading,
        public readonly ?array $optionLoadingPath,
        public readonly ?string $optionLoaded,
        public readonly ?array $optionLoadedPath,
        public readonly ?string $uploadFiles,
        public readonly ?array $uploadFilesPath,
        public readonly bool $registerDependenciesOnMount,
        public readonly bool $initializeOptionsOnMount,
        public readonly bool $initializeUploadsOnMount,
        public readonly FormRenderOptions $renderOptions,
    ) {
    }

    public function simpleRuntimeState(Form $form): array
    {
        $schema = $form->schema();

        return array_filter([
            $this->model => $schema->defaults(),
            $this->rules => $schema->rules(),
            $this->optionState => $this->buildInitialOptionState($schema),
            $this->optionLoading => $this->buildFlagState($schema->remoteOptionPaths()),
            $this->optionLoaded => $this->buildFlagState($schema->remoteOptionPaths()),
            $this->uploadFiles => $this->buildInitialUploadState($schema->uploadPaths()),
        ], static fn(mixed $value, mixed $key) => is_string($key) && $key !== '', ARRAY_FILTER_USE_BOTH);
    }

    public function simpleRuntimeConfig(Form $form): array
    {
        $schema = $form->schema();

        return [
            'defaults' => $schema->defaults(),
            'rules' => $schema->rules(),
            'ref' => $this->ref,
            'modelVar' => $this->modelPath === null ? $this->model : null,
            'modelPath' => $this->modelPath,
            'rulesVar' => $this->rulesPath === null ? $this->rules : null,
            'rulesPath' => $this->rulesPath,
            'optionStateVar' => $this->optionStatePath === null ? $this->optionState : null,
            'optionStatePath' => $this->optionStatePath,
            'optionLoadingVar' => $this->optionLoadingPath === null ? $this->optionLoading : null,
            'optionLoadingPath' => $this->optionLoadingPath,
            'optionLoadedVar' => $this->optionLoadedPath === null ? $this->optionLoaded : null,
            'optionLoadedPath' => $this->optionLoadedPath,
            'uploadFilesVar' => $this->uploadFilesPath === null ? $this->uploadFiles : null,
            'uploadFilesPath' => $this->uploadFilesPath,
            'registerDependenciesOnMount' => $this->registerDependenciesOnMount,
            'initializeOptionsOnMount' => $this->initializeOptionsOnMount,
            'initializeUploadsOnMount' => $this->initializeUploadsOnMount,
            'events' => $form->getEventHandlers(),
            'remoteOptions' => $schema->remoteOptions(),
            'remoteOptionPaths' => $schema->remoteOptionPaths(),
            'selectOptions' => $schema->selectOptions(),
            'linkages' => $schema->linkages(),
            'uploads' => $schema->uploads(),
            'uploadPaths' => $schema->uploadPaths(),
            'arrayGroups' => array_map(
                static fn(FormArrayGroupSchema $groupSchema) => $groupSchema->toRuntimeConfig(),
                $schema->arrayGroups()
            ),
        ];
    }

    private function buildInitialOptionState(FormSchema $schema): array
    {
        $state = [];
        foreach ($schema->remoteOptionPaths() as $fieldPath) {
            FormPath::set(
                $state,
                $fieldPath,
                array_values(FormPath::get($schema->remoteOptions(), $fieldPath, [])['initialOptions'] ?? [])
            );
        }

        return $state;
    }

    private function buildFlagState(array $keys, bool $initial = false): array
    {
        $state = [];
        foreach ($keys as $key) {
            FormPath::set($state, $key, $initial);
        }

        return $state;
    }

    private function buildInitialUploadState(array $keys): array
    {
        $state = [];
        foreach ($keys as $key) {
            FormPath::set($state, $key, []);
        }

        return $state;
    }
}
