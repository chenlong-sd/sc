<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class FormRenderStateFactory
{
    public function createFilter(): FormRenderState
    {
        return $this->createNamedState(
            scope: FormScope::filter(),
            model: 'filterModel',
            ref: 'filterFormRef',
            rules: 'filterRules',
            optionState: 'filterOptions',
            optionLoading: 'filterOptionLoading',
            optionLoaded: 'filterOptionLoaded',
            uploadFiles: 'filterUploadFiles',
            remoteLoadMethod: 'loadFormFieldOptions',
            uploadSuccessMethod: 'handleUploadSuccess',
            uploadRemoveMethod: 'handleUploadRemove',
            uploadExceedMethod: 'handleUploadExceed',
            uploadPreviewMethod: 'handleUploadPreview',
            linkageMethod: 'applyFormLinkage',
            mode: 'filters',
            submitMethod: 'submitFilters',
            resetMethod: 'resetFilters',
        );
    }

    public function createStandalone(string $formKey): FormRenderState
    {
        $prefix = $this->jsStateVariable($formKey, '');

        return $this->createNamedState(
            scope: FormScope::standalone($formKey),
            model: $prefix . 'Model',
            ref: $prefix . 'FormRef',
            rules: $prefix . 'Rules',
            optionState: $prefix . 'Options',
            optionLoading: $prefix . 'OptionLoading',
            optionLoaded: $prefix . 'OptionLoaded',
            uploadFiles: $prefix . 'UploadFiles',
            remoteLoadMethod: 'loadSimpleFormFieldOptions',
            uploadSuccessMethod: 'handleSimpleUploadSuccess',
            uploadRemoveMethod: 'handleSimpleUploadRemove',
            uploadExceedMethod: 'handleSimpleUploadExceed',
            uploadPreviewMethod: 'handleSimpleUploadPreview',
            linkageMethod: 'applySimpleFormLinkage',
        );
    }

    public function createManagedDialog(string $dialogKey, ?string $dialogFormRef = null): FormRenderState
    {
        $quotedKey = $this->jsLiteral($dialogKey);
        $state = $this->createNamedState(
            scope: FormScope::dialog($dialogKey),
            model: sprintf('dialogForms[%s]', $quotedKey),
            ref: $dialogFormRef ?: ('dialogFormRef_' . $this->jsStateVariable($dialogKey, '')),
            rules: sprintf('dialogRules[%s]', $quotedKey),
            optionState: sprintf('dialogOptions[%s]', $quotedKey),
            optionLoading: sprintf('dialogOptionLoading[%s]', $quotedKey),
            optionLoaded: sprintf('dialogOptionLoaded[%s]', $quotedKey),
            uploadFiles: sprintf('dialogUploadFiles[%s]', $quotedKey),
            remoteLoadMethod: 'loadFormFieldOptions',
            uploadSuccessMethod: 'handleUploadSuccess',
            uploadRemoveMethod: 'handleUploadRemove',
            uploadExceedMethod: 'handleUploadExceed',
            uploadPreviewMethod: 'handleUploadPreview',
            linkageMethod: 'applyFormLinkage',
        );

        return new FormRenderState(
            scope: $state->scope,
            model: $state->model,
            modelPath: ['dialogForms', $dialogKey],
            ref: $state->ref,
            rules: $state->rules,
            rulesPath: ['dialogRules', $dialogKey],
            optionState: $state->optionState,
            optionStatePath: ['dialogOptions', $dialogKey],
            optionLoading: $state->optionLoading,
            optionLoadingPath: ['dialogOptionLoading', $dialogKey],
            optionLoaded: $state->optionLoaded,
            optionLoadedPath: ['dialogOptionLoaded', $dialogKey],
            uploadFiles: $state->uploadFiles,
            uploadFilesPath: ['dialogUploadFiles', $dialogKey],
            registerDependenciesOnMount: true,
            initializeOptionsOnMount: false,
            initializeUploadsOnMount: true,
            renderOptions: $state->renderOptions,
        );
    }

    public function createStandaloneDialog(): FormRenderState
    {
        return $this->createNamedState(
            scope: FormScope::dialog(),
            model: 'dialogForm',
            ref: 'dialogFormRef',
            rules: 'dialogRules',
            optionState: 'dialogOptions',
            optionLoading: 'dialogOptionLoading',
            optionLoaded: 'dialogOptionLoaded',
            uploadFiles: 'dialogUploadFiles',
            remoteLoadMethod: 'loadFormFieldOptions',
            uploadSuccessMethod: 'handleUploadSuccess',
            uploadRemoveMethod: 'handleUploadRemove',
            uploadExceedMethod: 'handleUploadExceed',
            uploadPreviewMethod: 'handleUploadPreview',
            linkageMethod: 'applyFormLinkage',
        );
    }

    private function createNamedState(
        FormScope $scope,
        string $model,
        ?string $ref,
        ?string $rules,
        ?string $optionState,
        ?string $optionLoading,
        ?string $optionLoaded,
        ?string $uploadFiles,
        string $remoteLoadMethod,
        string $uploadSuccessMethod,
        string $uploadRemoveMethod,
        string $uploadExceedMethod,
        string $uploadPreviewMethod,
        ?string $linkageMethod = null,
        string $mode = 'default',
        ?string $submitMethod = null,
        ?string $resetMethod = null,
        bool $registerDependenciesOnMount = true,
        bool $initializeOptionsOnMount = true,
        bool $initializeUploadsOnMount = true,
    ): FormRenderState {
        return new FormRenderState(
            scope: $scope,
            model: $model,
            modelPath: null,
            ref: $ref,
            rules: $rules,
            rulesPath: null,
            optionState: $optionState,
            optionStatePath: null,
            optionLoading: $optionLoading,
            optionLoadingPath: null,
            optionLoaded: $optionLoaded,
            optionLoadedPath: null,
            uploadFiles: $uploadFiles,
            uploadFilesPath: null,
            registerDependenciesOnMount: $registerDependenciesOnMount,
            initializeOptionsOnMount: $initializeOptionsOnMount,
            initializeUploadsOnMount: $initializeUploadsOnMount,
            renderOptions: FormRenderOptions::fromArray([
                'mode' => $mode,
                'ref' => $ref,
                'rules' => $rules,
                'submitMethod' => $submitMethod,
                'resetMethod' => $resetMethod,
                'remoteOptionsState' => $optionState,
                'remoteLoadingState' => $optionLoading,
                'remoteLoadMethod' => $remoteLoadMethod,
                'remoteScope' => $scope->value(),
                'uploadFilesState' => $uploadFiles,
                'uploadScope' => $scope->value(),
                'uploadSuccessMethod' => $uploadSuccessMethod,
                'uploadRemoveMethod' => $uploadRemoveMethod,
                'uploadExceedMethod' => $uploadExceedMethod,
                'uploadPreviewMethod' => $uploadPreviewMethod,
                'linkageMethod' => $linkageMethod,
            ]),
        );
    }

    private function jsStateVariable(string $key, string $suffix): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_$]+/', '_', $key) ?: 'form';
        if (preg_match('/^[0-9]/', $normalized)) {
            $normalized = 'v2_' . $normalized;
        }

        return $normalized . $suffix;
    }

    private function jsLiteral(string $value): string
    {
        return "'" . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            $value
        ) . "'";
    }
}
