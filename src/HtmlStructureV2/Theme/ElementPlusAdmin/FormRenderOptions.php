<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class FormRenderOptions
{
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
        public readonly ?string $uploadFilesState = null,
        public readonly ?string $uploadScope = null,
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
            uploadFilesState: self::stringOrNull($options, 'uploadFilesState'),
            uploadScope: self::stringOrNull($options, 'uploadScope'),
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
        return sprintf("(%s['%s'] || [])", $this->remoteOptionsState, $fieldName);
    }

    public function remoteLoadingExpression(string $fieldName): string
    {
        return sprintf("%s['%s'] || false", $this->remoteLoadingState, $fieldName);
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

    public function linkageChangeHandler(string $fieldName): string
    {
        return sprintf(
            "(value) => %s('%s', '%s', value)",
            $this->linkageMethod,
            $this->remoteScope,
            $fieldName
        );
    }

    public function uploadFileListExpression(string $fieldName): string
    {
        return sprintf("%s['%s']", $this->uploadFilesState, $fieldName);
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

    public function uploadRemoveHandler(string $fieldName): string
    {
        return sprintf(
            "(uploadFile, uploadFiles) => %s('%s', '%s', uploadFile, uploadFiles)",
            $this->uploadRemoveMethod,
            $this->uploadScope,
            $fieldName
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
}
