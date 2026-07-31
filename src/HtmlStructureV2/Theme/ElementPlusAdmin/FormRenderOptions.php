<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

/**
 * 表单渲染期的运行时上下文（各类 state 变量名与运行时方法名）。
 *
 * 这里的 `*Handler()` / `*Expression()` 统一接收「已经编码好的字段路径 JS 表达式」，
 * 而不是裸字段名：静态字段用 `jsLiteral('a.b')`，数组行内的动态路径直接传
 * `joinFormArrayFieldPath(...)` 之类的表达式即可，无需再为两种形态各留一个方法。
 *
 * 例外是 optionExpression() / remoteLoadingExpression() / uploadFileListExpression()：
 * 它们的静态与动态形态生成的是两套不同的取值代码（属性访问器 vs `getFormPathStateValue()`），
 * 所以仍保留 `*ByPathExpression()` 变体。
 */
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

    public function hasFieldOptionsContext(): bool
    {
        return $this->remoteScope !== null;
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

    /**
     * 静态字段名版本：直接生成 `state?.a?.b` 这样的属性访问器。
     * 与 optionExpressionByPathExpression() 不是同一套实现，不能合并。
     */
    public function optionExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->remoteOptionsState, $fieldName, '[]');
    }

    /**
     * 动态路径版本：路径要到运行时才知道，只能走 `getFormPathStateValue()` 取值。
     */
    public function optionExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->remoteOptionsState, $fieldPathExpression, '[]');
    }

    public function fieldOptionsExpression(string $fieldPathExpression): string
    {
        return $this->scopedCall('getFieldOptions', $this->remoteScope, $fieldPathExpression);
    }

    /** @see optionExpression() 为什么这一对不能合并 */
    public function remoteLoadingExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->remoteLoadingState, $fieldName, 'false');
    }

    public function remoteLoadingExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->remoteLoadingState, $fieldPathExpression, 'false');
    }

    public function remoteVisibleChangeHandler(string $fieldPathExpression): string
    {
        return sprintf(
            '(visible) => visible && %s',
            $this->scopedCall($this->remoteLoadMethod, $this->remoteScope, $fieldPathExpression)
        );
    }

    public function remoteSearchHandler(string $fieldPathExpression): string
    {
        return sprintf(
            '(query) => %s',
            $this->scopedCall($this->remoteLoadMethod, $this->remoteScope, $fieldPathExpression, 'true', 'query')
        );
    }

    public function linkageChangeHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler($this->linkageMethod, $this->remoteScope, $fieldPathExpression, 'value');
    }

    public function fieldValueUpdateHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->fieldValueUpdateMethod,
            $this->remoteScope,
            $fieldPathExpression,
            'value'
        );
    }

    /** @see optionExpression() 为什么这一对不能合并 */
    public function uploadFileListExpression(string $fieldName): string
    {
        return $this->pathStateExpression($this->uploadFilesState, $fieldName, '[]');
    }

    public function uploadFileListExpressionByPathExpression(string $fieldPathExpression): string
    {
        return $this->dynamicPathStateExpression($this->uploadFilesState, $fieldPathExpression, '[]');
    }

    public function uploadFileListUpdateHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadFileListUpdateMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'uploadFiles'
        );
    }

    public function uploadSuccessHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadSuccessMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'response',
            'uploadFile',
            'uploadFiles'
        );
    }

    public function uploadBeforeHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadBeforeMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'uploadRawFile'
        );
    }

    public function uploadErrorHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadErrorMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'error',
            'uploadFile',
            'uploadFiles'
        );
    }

    public function uploadRemoveHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadRemoveMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'uploadFile',
            'uploadFiles'
        );
    }

    public function uploadExceedHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadExceedMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'files',
            'uploadFiles'
        );
    }

    public function uploadProgressHandler(string $fieldPathExpression): string
    {
        return $this->scopedFieldHandler(
            $this->uploadProgressMethod,
            $this->uploadScope,
            $fieldPathExpression,
            'uploadEvent',
            'uploadFile',
            'uploadFiles'
        );
    }

    public function pickerItemsExpression(string $fieldPathExpression): string
    {
        return $this->scopedCall($this->pickerItemsMethod, $this->pickerScope, $fieldPathExpression);
    }

    public function pickerOpenExpression(string $fieldPathExpression, ?string $dialogKey = null): string
    {
        return $this->scopedCall(
            $this->pickerOpenMethod,
            $this->pickerScope,
            $fieldPathExpression,
            $dialogKey === null ? 'null' : $this->jsLiteral($dialogKey)
        );
    }

    public function pickerRemoveExpression(string $fieldPathExpression, string $valueExpression): string
    {
        return $this->scopedCall(
            $this->pickerRemoveMethod,
            $this->pickerScope,
            $fieldPathExpression,
            $valueExpression
        );
    }

    public function pickerClearExpression(string $fieldPathExpression): string
    {
        return $this->scopedCall($this->pickerClearMethod, $this->pickerScope, $fieldPathExpression);
    }

    public function pickerDisplayExpression(string $fieldPathExpression, string $itemExpression = 'item'): string
    {
        return $this->scopedCall(
            $this->pickerDisplayMethod,
            $this->pickerScope,
            $fieldPathExpression,
            $itemExpression
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

    /**
     * 组装 `method('scope', <字段路径表达式>, ...额外参数)` 形式的运行时调用。
     */
    private function scopedCall(
        string $method,
        ?string $scope,
        string $fieldPathExpression,
        string ...$extraArguments
    ): string {
        return sprintf(
            '%s(%s)',
            $method,
            implode(', ', [$this->jsLiteral($scope), $fieldPathExpression, ...$extraArguments])
        );
    }

    /**
     * 组装 `(p1, p2) => method('scope', <字段路径表达式>, p1, p2)` 形式的事件处理器：
     * 箭头函数形参会原样透传给运行时方法。
     */
    private function scopedFieldHandler(
        string $method,
        ?string $scope,
        string $fieldPathExpression,
        string ...$parameters
    ): string {
        return sprintf(
            '(%s) => %s',
            implode(', ', $parameters),
            $this->scopedCall($method, $scope, $fieldPathExpression, ...$parameters)
        );
    }
}
