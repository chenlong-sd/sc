<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

final class FormRenderOptions
{
    use BuildsJsExpressions;

    public function __construct(
        public readonly string $mode = 'default',
        public readonly ?string $formScope = null,
        public readonly bool $showLabels = true,
        public readonly ?string $ref = null,
        public readonly ?string $rules = null,
        public readonly ?string $submitMethod = null,
        public readonly ?string $resetMethod = null,
        public readonly ?string $remoteOptionsState = null,
        public readonly ?string $remoteLoadingState = null,
        public readonly ?string $remoteLoadMethod = null,
        public readonly ?string $remoteScope = null,
        public readonly string $fieldValueUpdateMethod = 'setFormPathValue',
        public readonly ?string $uploadFilesState = null,
        public readonly ?string $uploadScope = null,
        public readonly string $uploadFileListUpdateMethod = 'setUploadFileList',
        public readonly string $uploadBeforeMethod = 'handleUploadBefore',
        public readonly string $uploadSuccessMethod = 'handleUploadSuccess',
        public readonly string $uploadErrorMethod = 'handleUploadError',
        public readonly string $uploadRemoveMethod = 'handleUploadRemove',
        public readonly string $uploadExceedMethod = 'handleUploadExceed',
        public readonly string $uploadProgressMethod = 'handleUploadProgress',
        public readonly string $uploadPreviewMethod = 'handleUploadPreview',
        public readonly ?string $pickerScope = null,
        public readonly string $pickerItemsMethod = 'getPickerItems',
        public readonly string $pickerOpenMethod = 'openPickerDialog',
        public readonly string $pickerRemoveMethod = 'removePickerItem',
        public readonly string $pickerClearMethod = 'clearPickerField',
        public readonly string $pickerDisplayMethod = 'resolvePickerItemDisplay',
        public readonly ?string $linkageMethod = null,
    ) {
    }

    public static function fromArray(array $options): self
    {
        return new self(
            mode: self::stringOrDefault($options, 'mode', 'default'),
            formScope: self::stringOrNull($options, 'formScope'),
            showLabels: self::boolOrDefault($options, 'showLabels', true),
            ref: self::stringOrNull($options, 'ref'),
            rules: self::stringOrNull($options, 'rules'),
            submitMethod: self::stringOrNull($options, 'submitMethod'),
            resetMethod: self::stringOrNull($options, 'resetMethod'),
            remoteOptionsState: self::stringOrNull($options, 'remoteOptionsState'),
            remoteLoadingState: self::stringOrNull($options, 'remoteLoadingState'),
            remoteLoadMethod: self::stringOrNull($options, 'remoteLoadMethod'),
            remoteScope: self::stringOrNull($options, 'remoteScope'),
            fieldValueUpdateMethod: self::stringOrDefault($options, 'fieldValueUpdateMethod', 'setFormPathValue'),
            uploadFilesState: self::stringOrNull($options, 'uploadFilesState'),
            uploadScope: self::stringOrNull($options, 'uploadScope'),
            uploadFileListUpdateMethod: self::stringOrDefault($options, 'uploadFileListUpdateMethod', 'setUploadFileList'),
            uploadBeforeMethod: self::stringOrDefault($options, 'uploadBeforeMethod', 'handleUploadBefore'),
            uploadSuccessMethod: self::stringOrDefault($options, 'uploadSuccessMethod', 'handleUploadSuccess'),
            uploadErrorMethod: self::stringOrDefault($options, 'uploadErrorMethod', 'handleUploadError'),
            uploadRemoveMethod: self::stringOrDefault($options, 'uploadRemoveMethod', 'handleUploadRemove'),
            uploadExceedMethod: self::stringOrDefault($options, 'uploadExceedMethod', 'handleUploadExceed'),
            uploadProgressMethod: self::stringOrDefault($options, 'uploadProgressMethod', 'handleUploadProgress'),
            uploadPreviewMethod: self::stringOrDefault($options, 'uploadPreviewMethod', 'handleUploadPreview'),
            pickerScope: self::stringOrNull($options, 'pickerScope'),
            pickerItemsMethod: self::stringOrDefault($options, 'pickerItemsMethod', 'getPickerItems'),
            pickerOpenMethod: self::stringOrDefault($options, 'pickerOpenMethod', 'openPickerDialog'),
            pickerRemoveMethod: self::stringOrDefault($options, 'pickerRemoveMethod', 'removePickerItem'),
            pickerClearMethod: self::stringOrDefault($options, 'pickerClearMethod', 'clearPickerField'),
            pickerDisplayMethod: self::stringOrDefault($options, 'pickerDisplayMethod', 'resolvePickerItemDisplay'),
            linkageMethod: self::stringOrNull($options, 'linkageMethod'),
        );
    }

    public function isFilterMode(): bool
    {
        return $this->mode === 'filters';
    }

    public function hasRemoteOptionsContext(): bool
    {
        return $this->remoteOptionsState !== null
            && $this->remoteLoadingState !== null
            && $this->remoteLoadMethod !== null
            && $this->remoteScope !== null;
    }

    public function hasOptionStateContext(): bool
    {
        return $this->remoteOptionsState !== null;
    }

    public function hasUploadContext(): bool
    {
        return $this->uploadFilesState !== null
            && $this->uploadScope !== null;
    }

    public function hasPickerContext(): bool
    {
        return $this->pickerScope !== null
            && $this->pickerItemsMethod !== ''
            && $this->pickerOpenMethod !== ''
            && $this->pickerRemoveMethod !== ''
            && $this->pickerClearMethod !== ''
            && $this->pickerDisplayMethod !== '';
    }

    public function hasLinkageContext(): bool
    {
        return $this->linkageMethod !== null
            && $this->remoteScope !== null;
    }

    public function remoteOptionsExpression(string $fieldName): string
    {
        return $this->optionExpression($fieldName);
    }

    public function remoteOptionsExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->optionExpressionByPathExpression($fieldPathExpression);
    }

    public function optionExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->remoteOptionsState, $fieldName, '[]');
    }

    public function optionExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->remoteOptionsState, $fieldPathExpression, '[]');
    }

    public function remoteLoadingExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->remoteLoadingState, $fieldName, 'false');
    }

    public function remoteLoadingExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->remoteLoadingState, $fieldPathExpression, 'false');
    }

    public function remoteVisibleChangeHandler(string $fieldName): string
    {
        return sprintf(
            "(visible) => visible && %s('%s', '%s')",
            $this->remoteLoadMethod,
            $this->remoteScope,
            $fieldName
        );
    }

    public function withShowLabels(bool $showLabels): self
    {
        return new self(
            mode: $this->mode,
            formScope: $this->formScope,
            showLabels: $showLabels,
            ref: $this->ref,
            rules: $this->rules,
            submitMethod: $this->submitMethod,
            resetMethod: $this->resetMethod,
            remoteOptionsState: $this->remoteOptionsState,
            remoteLoadingState: $this->remoteLoadingState,
            remoteLoadMethod: $this->remoteLoadMethod,
            remoteScope: $this->remoteScope,
            fieldValueUpdateMethod: $this->fieldValueUpdateMethod,
            uploadFilesState: $this->uploadFilesState,
            uploadScope: $this->uploadScope,
            uploadFileListUpdateMethod: $this->uploadFileListUpdateMethod,
            uploadBeforeMethod: $this->uploadBeforeMethod,
            uploadSuccessMethod: $this->uploadSuccessMethod,
            uploadErrorMethod: $this->uploadErrorMethod,
            uploadRemoveMethod: $this->uploadRemoveMethod,
            uploadExceedMethod: $this->uploadExceedMethod,
            uploadProgressMethod: $this->uploadProgressMethod,
            uploadPreviewMethod: $this->uploadPreviewMethod,
            pickerScope: $this->pickerScope,
            pickerItemsMethod: $this->pickerItemsMethod,
            pickerOpenMethod: $this->pickerOpenMethod,
            pickerRemoveMethod: $this->pickerRemoveMethod,
            pickerClearMethod: $this->pickerClearMethod,
            pickerDisplayMethod: $this->pickerDisplayMethod,
            linkageMethod: $this->linkageMethod,
        );
    }

    public function remoteVisibleChangeHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(visible) => visible && %s('%s', %s)",
            $this->remoteLoadMethod,
            $this->remoteScope,
            $fieldPathExpression
        );
    }

    public function linkageChangeHandler(string $fieldName): string
    {
        return sprintf(
            "(value) => %s('%s', '%s', value)",
            $this->linkageMethod,
            $this->remoteScope,
            $fieldName
        );
    }

    public function linkageChangeHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(value) => %s('%s', %s, value)",
            $this->linkageMethod,
            $this->remoteScope,
            $fieldPathExpression
        );
    }

    public function fieldValueUpdateHandler(string $fieldName): string
    {
        return sprintf(
            "(value) => %s('%s', '%s', value)",
            $this->fieldValueUpdateMethod,
            $this->remoteScope,
            $fieldName
        );
    }

    public function fieldValueUpdateHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(value) => %s('%s', %s, value)",
            $this->fieldValueUpdateMethod,
            $this->remoteScope,
            $fieldPathExpression
        );
    }

    public function uploadFileListExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->uploadFilesState, $fieldName, '[]');
    }

    public function uploadFileListExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->uploadFilesState, $fieldPathExpression, '[]');
    }

    public function uploadFileListUpdateHandler(string $fieldName): string
    {
        return sprintf(
            "(uploadFiles) => %s('%s', '%s', uploadFiles)",
            $this->uploadFileListUpdateMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadFileListUpdateHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(uploadFiles) => %s('%s', %s, uploadFiles)",
            $this->uploadFileListUpdateMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadSuccessHandler(string $fieldName): string
    {
        return sprintf(
            "(response, uploadFile, uploadFiles) => %s('%s', '%s', response, uploadFile, uploadFiles)",
            $this->uploadSuccessMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadSuccessHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(response, uploadFile, uploadFiles) => %s('%s', %s, response, uploadFile, uploadFiles)",
            $this->uploadSuccessMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadBeforeHandler(string $fieldName): string
    {
        return sprintf(
            "(uploadRawFile) => %s('%s', '%s', uploadRawFile)",
            $this->uploadBeforeMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadBeforeHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(uploadRawFile) => %s('%s', %s, uploadRawFile)",
            $this->uploadBeforeMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadErrorHandler(string $fieldName): string
    {
        return sprintf(
            "(error, uploadFile, uploadFiles) => %s('%s', '%s', error, uploadFile, uploadFiles)",
            $this->uploadErrorMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadErrorHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(error, uploadFile, uploadFiles) => %s('%s', %s, error, uploadFile, uploadFiles)",
            $this->uploadErrorMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadRemoveHandler(string $fieldName): string
    {
        return sprintf(
            "(uploadFile, uploadFiles) => %s('%s', '%s', uploadFile, uploadFiles)",
            $this->uploadRemoveMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadRemoveHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(uploadFile, uploadFiles) => %s('%s', %s, uploadFile, uploadFiles)",
            $this->uploadRemoveMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadExceedHandler(string $fieldName): string
    {
        return sprintf(
            "(files, uploadFiles) => %s('%s', '%s', files, uploadFiles)",
            $this->uploadExceedMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadExceedHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(files, uploadFiles) => %s('%s', %s, files, uploadFiles)",
            $this->uploadExceedMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function uploadProgressHandler(string $fieldName): string
    {
        return sprintf(
            "(uploadEvent, uploadFile, uploadFiles) => %s('%s', '%s', uploadEvent, uploadFile, uploadFiles)",
            $this->uploadProgressMethod,
            $this->uploadScope,
            $fieldName
        );
    }

    public function uploadProgressHandlerByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "(uploadEvent, uploadFile, uploadFiles) => %s('%s', %s, uploadEvent, uploadFile, uploadFiles)",
            $this->uploadProgressMethod,
            $this->uploadScope,
            $fieldPathExpression
        );
    }

    public function pickerItemsExpression(string $fieldName): string
    {
        return sprintf(
            "%s(%s, %s)",
            $this->pickerItemsMethod,
            $this->jsLiteral($this->pickerScope),
            $this->jsLiteral($fieldName)
        );
    }

    public function pickerItemsExpressionByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "%s(%s, %s)",
            $this->pickerItemsMethod,
            $this->jsLiteral($this->pickerScope),
            $fieldPathExpression
        );
    }

    public function pickerOpenExpression(string $fieldName, ?string $dialogKey = null): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerOpenMethod,
            $this->jsLiteral($this->pickerScope),
            $this->jsLiteral($fieldName),
            $dialogKey === null ? 'null' : $this->jsLiteral($dialogKey)
        );
    }

    public function pickerOpenExpressionByPathExpression(string $fieldPathExpression, ?string $dialogKey = null): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerOpenMethod,
            $this->jsLiteral($this->pickerScope),
            $fieldPathExpression,
            $dialogKey === null ? 'null' : $this->jsLiteral($dialogKey)
        );
    }

    public function pickerRemoveExpression(string $fieldName, string $valueExpression): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerRemoveMethod,
            $this->jsLiteral($this->pickerScope),
            $this->jsLiteral($fieldName),
            $valueExpression
        );
    }

    public function pickerRemoveExpressionByPathExpression(string $fieldPathExpression, string $valueExpression): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerRemoveMethod,
            $this->jsLiteral($this->pickerScope),
            $fieldPathExpression,
            $valueExpression
        );
    }

    public function pickerClearExpression(string $fieldName): string
    {
        return sprintf(
            "%s(%s, %s)",
            $this->pickerClearMethod,
            $this->jsLiteral($this->pickerScope),
            $this->jsLiteral($fieldName)
        );
    }

    public function pickerClearExpressionByPathExpression(string $fieldPathExpression): string
    {
        return sprintf(
            "%s(%s, %s)",
            $this->pickerClearMethod,
            $this->jsLiteral($this->pickerScope),
            $fieldPathExpression
        );
    }

    public function pickerDisplayExpression(string $fieldName, string $itemExpression = 'item'): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerDisplayMethod,
            $this->jsLiteral($this->pickerScope),
            $this->jsLiteral($fieldName),
            $itemExpression
        );
    }

    public function pickerDisplayExpressionByPathExpression(string $fieldPathExpression, string $itemExpression = 'item'): string
    {
        return sprintf(
            "%s(%s, %s, %s)",
            $this->pickerDisplayMethod,
            $this->jsLiteral($this->pickerScope),
            $fieldPathExpression,
            $itemExpression
        );
    }

    private static function stringOrNull(array $options, string $key): ?string
    {
        $value = $options[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    private static function stringOrDefault(array $options, string $key, string $default): string
    {
        return self::stringOrNull($options, $key) ?? $default;
    }

    private static function boolOrDefault(array $options, string $key, bool $default): bool
    {
        $value = $options[$key] ?? null;
        if (is_bool($value)) {
            return $value;
        }

        return $default;
    }

    private function pathStateExpression(?string $root, string $path, string $fallback): string
    {
        if ($root === null) {
            return $fallback;
        }

        return sprintf('(%s ?? %s)', $this->jsReadableAccessor($root, $path), $fallback);
    }

    private function dynamicPathStateExpression(?string $root, string $pathExpression, string $fallback): string
    {
        if ($root === null) {
            return $fallback;
        }

        return sprintf('getFormPathStateValue(%s, %s, %s)', $root, $pathExpression, $fallback);
    }
}
