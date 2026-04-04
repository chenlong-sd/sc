<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Components\Form;

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
        return array_filter([
            $this->model => $form->defaults(),
            $this->rules => $form->rules(),
            $this->optionState => $this->buildInitialOptionState($form->remoteOptions()),
            $this->optionLoading => $this->buildFlagState(array_keys($form->remoteOptions())),
            $this->optionLoaded => $this->buildFlagState(array_keys($form->remoteOptions())),
            $this->uploadFiles => [],
        ], static fn(mixed $value, mixed $key) => is_string($key) && $key !== '', ARRAY_FILTER_USE_BOTH);
    }

    public function simpleRuntimeConfig(Form $form): array
    {
        return [
            'defaults' => $form->defaults(),
            'rules' => $form->rules(),
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
            'remoteOptions' => $form->remoteOptions(),
            'selectOptions' => $form->selectOptions(),
            'linkages' => $form->linkages(),
            'uploads' => $form->uploads(),
        ];
    }

    private function buildInitialOptionState(array $remoteOptions): array
    {
        $state = [];
        foreach ($remoteOptions as $fieldName => $fieldConfig) {
            $state[$fieldName] = array_values($fieldConfig['initialOptions'] ?? []);
        }

        return $state;
    }

    private function buildFlagState(array $keys, bool $initial = false): array
    {
        $state = [];
        foreach ($keys as $key) {
            $state[$key] = $initial;
        }

        return $state;
    }
}
