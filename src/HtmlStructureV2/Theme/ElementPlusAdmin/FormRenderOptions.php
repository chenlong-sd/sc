<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

final class FormRenderOptions
{
    use BuildsJsExpressions;

    public function __construct(
        public readonly string $mode = 'default',
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
        public readonly string $uploadSuccessMethod = 'handleUploadSuccess',
        public readonly string $uploadRemoveMethod = 'handleUploadRemove',
        public readonly string $uploadExceedMethod = 'handleUploadExceed',
        public readonly string $uploadPreviewMethod = 'handleUploadPreview',
        public readonly ?string $linkageMethod = null,
    ) {
    }

    public static function fromArray(array $options): self
    {
        return new self(
            mode: self::stringOrDefault($options, 'mode', 'default'),
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
            uploadSuccessMethod: self::stringOrDefault($options, 'uploadSuccessMethod', 'handleUploadSuccess'),
            uploadRemoveMethod: self::stringOrDefault($options, 'uploadRemoveMethod', 'handleUploadRemove'),
            uploadExceedMethod: self::stringOrDefault($options, 'uploadExceedMethod', 'handleUploadExceed'),
            uploadPreviewMethod: self::stringOrDefault($options, 'uploadPreviewMethod', 'handleUploadPreview'),
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

    public function hasUploadContext(): bool
    {
        return $this->uploadFilesState !== null
            && $this->uploadScope !== null;
    }

    public function hasLinkageContext(): bool
    {
        return $this->linkageMethod !== null
            && $this->remoteScope !== null;
    }

    public function remoteOptionsExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->remoteOptionsState, $fieldName, '[]');
    }

    public function remoteOptionsExpressionByPathExpression(string $fieldPathExpression): string
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
