<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Fields\CascaderField;
use Sc\Util\HtmlStructureV2\Components\Fields\EditorField;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\PickerField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\AppliesRenderableAttributes;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class FieldRenderer
{
    use AppliesRenderableAttributes;
    use BuildsJsExpressions;
    use EncodesJsValues;

    public function __construct(
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function render(
        Field $field,
        string $modelName,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
            renderContext: $renderContext,
        );
    }

    public function renderScoped(
        Field $field,
        string $modelName,
        string $propExpression,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
            propExpression: $propExpression,
            renderContext: $renderContext,
        );
    }

    public function renderTableCell(
        Field $field,
        string $fieldModelName,
        string $fieldPath,
        string $propExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement {
        return $this->renderField(
            field: $field,
            modelName: $fieldModelName,
            fieldPath: $fieldPath,
            inline: false,
            options: $options,
            propExpression: $propExpression,
            tableCell: true,
            renderContext: $renderContext,
        );
    }

    private function renderField(
        Field $field,
        string $modelName,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options,
        ?string $propExpression = null,
        bool $tableCell = false,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement {
        if ($field->type() === FieldType::HIDDEN) {
            return El::fictitious();
        }

        $modelAccessor = $this->jsModelAccessor($modelName, $field->name());
        $visibleWhen = $this->normalizeFieldExpression($field->getVisibleWhen(), $modelName);
        $disabledWhen = $this->normalizeFieldExpression($field->getDisabledWhen(), $modelName);
        $optionField = $field instanceof OptionField ? $field : null;
        $pickerField = $field instanceof PickerField ? $field : null;
        $uploadField = $field instanceof UploadField ? $field : null;
        $editorField = $field instanceof EditorField ? $field : null;
        $placeholderField = $field instanceof PlaceholderFieldInterface ? $field : null;
        $validatableField = $field instanceof ValidatableFieldInterface ? $field : null;
        $hasRemoteOptions = $optionField?->hasRemoteOptions() && $options->hasRemoteOptionsContext();

        if ($editorField !== null && $renderContext !== null) {
            $this->ensureEditorAssets($renderContext);
        }

        $item = $this->buildRenderItem(
            $field,
            $fieldPath,
            $validatableField,
            $propExpression,
            $tableCell,
            $options->showLabels && $field->hasLabel()
        );
        if ($pickerField !== null) {
            $component = $this->buildPickerComponent($pickerField, $fieldPath, $options, $propExpression, $disabledWhen);
        } else {
            $usesExplicitModelBinding = $propExpression !== null;
            $component = $this->buildFieldComponent(
                $field,
                $modelAccessor,
                $inline,
                $placeholderField,
                $uploadField,
                $usesExplicitModelBinding
                    ? ($propExpression === null
                        ? $options->fieldValueUpdateHandler($fieldPath)
                        : $options->fieldValueUpdateHandlerByPathExpression($propExpression))
                    : null
            );

            $this->applyFieldProps($component, $field);
            $this->applyDisabledState($component, $field, $disabledWhen);
            $this->applyOptionFieldBehavior($component, $field, $fieldPath, $optionField, $hasRemoteOptions, $options, $propExpression);
            $this->applyUploadFieldBehavior($component, $fieldPath, $field, $uploadField, $options, $propExpression);
        }

        $item->append($this->wrapFieldControl($field, $component, $renderContext));
        $this->appendHelpText($item, $field);

        if ($tableCell) {
            if ($visibleWhen !== null) {
                $item->setAttr('v-if', $visibleWhen);
            }

            return $item;
        }

        return $this->wrapFieldRoot($field, $item, $inline, $visibleWhen);
    }

    private function buildFieldItem(
        Field $field,
        string $fieldPath,
        ?ValidatableFieldInterface $validatableField,
        bool $showLabels
    ): DoubleLabel
    {
        $item = El::double('el-form-item')
            ->setAttr('prop', $fieldPath);

        if ($showLabels) {
            $item->setAttr('label', $field->label());
        } else {
            $item->setAttr('label-width', '0');
        }

        if ($validatableField?->isRequired()) {
            $item->setAttr('required');
        }

        return $item;
    }

    private function buildRenderItem(
        Field $field,
        string $fieldPath,
        ?ValidatableFieldInterface $validatableField,
        ?string $propExpression,
        bool $tableCell,
        bool $showLabels
    ): DoubleLabel {
        if ($tableCell) {
            $item = El::double('el-form-item')->setAttrs([
                ':prop' => $propExpression ?? $fieldPath,
                'label-width' => '0',
                'class' => 'sc-v2-form-table__item',
            ]);

            if ($validatableField?->isRequired()) {
                $item->setAttr('required');
            }

            return $item;
        }

        if ($propExpression !== null) {
            $item = El::double('el-form-item')
                ->setAttr(':prop', $propExpression);

            if ($showLabels) {
                $item->setAttr('label', $field->label());
            } else {
                $item->setAttr('label-width', '0');
            }

            if ($validatableField?->isRequired()) {
                $item->setAttr('required');
            }

            return $item;
        }

        return $this->buildFieldItem($field, $fieldPath, $validatableField, $showLabels);
    }

    private function buildFieldComponent(
        Field $field,
        string $modelAccessor,
        bool $inline,
        ?PlaceholderFieldInterface $placeholderField,
        ?UploadField $uploadField,
        ?string $modelUpdateHandler = null
    ): AbstractHtmlElement {
        $placeholder = $placeholderField?->getPlaceholder() ?? '';
        $upload = $uploadField?->getUpload() ?? [];
        $useExplicitModelBinding = is_string($modelUpdateHandler) && $modelUpdateHandler !== '';

        $bindModelValue = static function (array $attrs) use ($modelAccessor, $modelUpdateHandler, $useExplicitModelBinding): array {
            if ($useExplicitModelBinding) {
                $attrs[':model-value'] = $modelAccessor;
                $attrs['@update:model-value'] = $modelUpdateHandler;

                return $attrs;
            }

            $attrs['v-model'] = $modelAccessor;

            return $attrs;
        };

        return match ($field->type()) {
            FieldType::TEXT => El::double('el-input')->setAttrs($bindModelValue([
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 192px' : 'width: 100%',
            ])),
            FieldType::PASSWORD => El::double('el-input')->setAttrs($bindModelValue([
                'type' => 'password',
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 192px' : 'width: 100%',
            ])),
            FieldType::ICON => El::double('sc-v2-icon-selector')->setAttrs($bindModelValue([
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 220px' : 'width: 100%',
            ])),
            FieldType::TEXTAREA => El::double('el-input')->setAttrs($bindModelValue([
                'type' => 'textarea',
                ':rows' => (string)($field->getProps()['rows'] ?? 4),
                'placeholder' => $placeholder,
            ])),
            FieldType::EDITOR => El::double('sc-v2-rich-editor')->setAttrs($bindModelValue([
                'placeholder' => $placeholder,
                ':config' => $this->jsValue($field instanceof EditorField ? $field->getEditorOptions() : []),
                'upload-url' => $field instanceof EditorField ? $field->getUploadUrl() : '',
                'style' => $inline ? 'width:min(860px,100%)' : 'width:100%',
            ])),
            FieldType::NUMBER => El::double('el-input-number')->setAttrs($bindModelValue([
                'style' => $inline ? 'width: 192px' : 'width: 100%',
            ])),
            FieldType::SELECT => El::double('el-select')->setAttrs($bindModelValue([
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 192px' : 'width: 100%',
            ])),
            FieldType::RADIO => El::double('el-radio-group')->setAttrs($bindModelValue([])),
            FieldType::CHECKBOX => El::double('el-checkbox-group')->setAttrs($bindModelValue([])),
            FieldType::CASCADER => El::double('el-cascader')->setAttrs($bindModelValue([
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 192px' : 'width: 100%',
            ])),
            FieldType::DATE => El::double('el-date-picker')->setAttrs($bindModelValue([
                'type' => 'date',
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 220px' : 'width: 100%',
            ])),
            FieldType::DATETIME => El::double('el-date-picker')->setAttrs($bindModelValue([
                'type' => 'datetime',
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 240px' : 'width: 100%',
            ])),
            FieldType::DATE_RANGE => El::double('el-date-picker')->setAttrs($bindModelValue([
                'type' => 'daterange',
                'range-separator' => (string)($field->getProps()['range-separator'] ?? '至'),
                'start-placeholder' => (string)($field->getProps()['start-placeholder'] ?? '开始日期'),
                'end-placeholder' => (string)($field->getProps()['end-placeholder'] ?? '结束日期'),
                'clearable' => '',
                'style' => $inline ? 'width: 320px' : 'width: 100%',
            ])),
            FieldType::UPLOAD => El::double('el-upload')->setAttrs(array_filter([
                'action' => (string)($upload['action'] ?? ''),
                'method' => (string)($upload['method'] ?? 'post'),
                'name' => (string)($upload['name'] ?? 'file'),
                ':headers' => $this->jsValue($upload['headers'] ?? []),
                ':data' => $this->jsValue($upload['data'] ?? []),
                ':multiple' => ($upload['multiple'] ?? false) ? 'true' : 'false',
                ':limit' => isset($upload['limit']) ? (string)$upload['limit'] : null,
                ':show-file-list' => 'true',
                'list-type' => (string)($upload['listType'] ?? 'text'),
                'accept' => $upload['accept'] ?? '',
            ], static fn(mixed $value) => $value !== null && $value !== '')),
            FieldType::SWITCH => El::double('el-switch')->setAttrs($bindModelValue([])),
            default => El::fictitious(),
        };
    }

    private function applyFieldProps(AbstractHtmlElement $component, Field $field): void
    {
        $props = [];

        foreach ($field->getProps() as $attr => $value) {
            if ($field->type() === FieldType::TEXTAREA && $attr === 'rows') {
                continue;
            }
            if ($field->type() === FieldType::DATE_RANGE && in_array($attr, ['range-separator', 'start-placeholder', 'end-placeholder'], true)) {
                continue;
            }

            $props[$attr] = $value;
        }

        $this->applyRenderableAttributes($component, $props);
    }

    private function applyDisabledState(AbstractHtmlElement $component, Field $field, ?string $disabledWhen): void
    {
        if ($disabledWhen !== null) {
            $component->setAttr(':disabled', $field->isDisabled() ? 'true' : $disabledWhen);

            return;
        }

        if ($field->isDisabled()) {
            $component->setAttr('disabled');
        }
    }

    private function applyOptionFieldBehavior(
        AbstractHtmlElement $component,
        Field $field,
        string $fieldPath,
        ?OptionField $optionField,
        bool $hasRemoteOptions,
        FormRenderOptions $options,
        ?string $fieldPathExpression = null
    ): void {
        if ($optionField === null) {
            return;
        }

        if (
            in_array($field->type(), [FieldType::SELECT, FieldType::RADIO], true)
            && $optionField->hasLinkageUpdates()
            && $options->hasLinkageContext()
        ) {
            $component->setAttr(
                '@change',
                $fieldPathExpression === null
                    ? $options->linkageChangeHandler($fieldPath)
                    : $options->linkageChangeHandlerByPathExpression($fieldPathExpression)
            );
        }

        $optionsExpression = $options->hasOptionStateContext()
            ? ($fieldPathExpression === null
                ? $options->optionExpression($fieldPath)
                : $options->optionExpressionByPathExpression($fieldPathExpression))
            : null;

        if ($field->type() === FieldType::SELECT) {
            if ($hasRemoteOptions) {
                $component->setAttr('filterable');
                $component->setAttr(
                    ':loading',
                    $fieldPathExpression === null
                        ? $options->remoteLoadingExpression($fieldPath)
                        : $options->remoteLoadingExpressionByPathExpression($fieldPathExpression)
                );
                $component->setAttr(
                    '@visible-change',
                    $fieldPathExpression === null
                        ? $options->remoteVisibleChangeHandler($fieldPath)
                        : $options->remoteVisibleChangeHandlerByPathExpression($fieldPathExpression)
                );
            }

            $this->appendOptionChildren($component, $optionField, $optionsExpression, 'el-option');

            return;
        }

        if (in_array($field->type(), [FieldType::RADIO, FieldType::CHECKBOX], true)) {
            $this->appendOptionChildren(
                $component,
                $optionField,
                $optionsExpression,
                $field->type() === FieldType::RADIO ? 'el-radio' : 'el-checkbox'
            );

            return;
        }

        if ($field->type() === FieldType::CASCADER) {
            $component->setAttr(
                ':options',
                $optionsExpression ?? $this->jsValue($optionField->getOptions())
            );

            if ($optionField instanceof CascaderField && $optionField->shouldCloseAfterSelection()) {
                $this->applyCascaderCloseAfterSelection($component, $fieldPath, $fieldPathExpression);
            }
        }
    }

    private function applyCascaderCloseAfterSelection(
        AbstractHtmlElement $component,
        string $fieldPath,
        ?string $fieldPathExpression = null
    ): void {
        $refExpression = $this->resolveCascaderRefExpression($component, $fieldPath, $fieldPathExpression);
        $existingHandler = $component->getAttr('@change');

        $component->setAttr(
            '@change',
            $this->buildCascaderCloseHandler(
                $refExpression,
                is_string($existingHandler) ? $existingHandler : null
            )
        );
    }

    private function resolveCascaderRefExpression(
        AbstractHtmlElement $component,
        string $fieldPath,
        ?string $fieldPathExpression = null
    ): string {
        $dynamicRef = $component->getAttr(':ref');
        if (is_string($dynamicRef) && trim($dynamicRef) !== '') {
            return $dynamicRef;
        }

        $staticRef = $component->getAttr('ref');
        if (is_string($staticRef) && trim($staticRef) !== '') {
            return $this->jsValue($staticRef);
        }

        if ($fieldPathExpression !== null) {
            $refExpression = sprintf("('sc-v2-cascader:' + (%s))", $fieldPathExpression);
            $component->setAttr(':ref', $refExpression);

            return $refExpression;
        }

        $refName = 'sc-v2-cascader:' . $fieldPath;
        $component->setAttr('ref', $refName);

        return $this->jsValue($refName);
    }

    private function buildCascaderCloseHandler(string $refExpression, ?string $existingHandler = null): string
    {
        $existingLogic = '';
        if (is_string($existingHandler) && trim($existingHandler) !== '') {
            $existingLogic = sprintf(
                'const __scExisting = (%s); if (typeof __scExisting === "function") { __scExisting(...args); } ',
                $existingHandler
            );
        }

        return sprintf(
            '(...args) => { const $event = args[0]; const __scRefKey = %s; %sconst __scRefRaw = $refs[__scRefKey]; const __scRef = Array.isArray(__scRefRaw) ? __scRefRaw[0] : __scRefRaw; if (__scRef && typeof __scRef.togglePopperVisible === "function") { __scRef.togglePopperVisible(false); } }',
            $refExpression,
            $existingLogic
        );
    }

    private function buildPickerComponent(
        PickerField $field,
        string $fieldPath,
        FormRenderOptions $options,
        ?string $fieldPathExpression = null,
        ?string $disabledWhen = null
    ): AbstractHtmlElement {
        if (!$options->hasPickerContext()) {
            throw new InvalidArgumentException(sprintf(
                'Picker field [%s] requires picker runtime context.',
                $field->name()
            ));
        }

        $dialogKey = $field->dialogKey();
        if ($dialogKey === null || $dialogKey === '') {
            throw new InvalidArgumentException(sprintf(
                'Picker field [%s] requires dialog().',
                $field->name()
            ));
        }

        $itemsExpression = $fieldPathExpression === null
            ? $options->pickerItemsExpression($fieldPath)
            : $options->pickerItemsExpressionByPathExpression($fieldPathExpression);
        $hasItemsExpression = sprintf('(%s).length > 0', $itemsExpression);
        $countExpression = sprintf('(%s).length', $itemsExpression);
        $openExpression = $fieldPathExpression === null
            ? $options->pickerOpenExpression($fieldPath, $dialogKey)
            : $options->pickerOpenExpressionByPathExpression($fieldPathExpression, $dialogKey);
        $removeExpression = $fieldPathExpression === null
            ? $options->pickerRemoveExpression($fieldPath, 'pickerItem.__pickerValue')
            : $options->pickerRemoveExpressionByPathExpression($fieldPathExpression, 'pickerItem.__pickerValue');
        $clearExpression = $fieldPathExpression === null
            ? $options->pickerClearExpression($fieldPath)
            : $options->pickerClearExpressionByPathExpression($fieldPathExpression);
        $displayExpression = $fieldPathExpression === null
            ? $options->pickerDisplayExpression($fieldPath, 'pickerItem')
            : $options->pickerDisplayExpressionByPathExpression($fieldPathExpression, 'pickerItem');
        $disabledExpression = $disabledWhen ?? ($field->isDisabled() ? 'true' : 'false');

        $root = El::double('div')->addClass('sc-v2-picker');
        $panel = El::double('div')
            ->addClass('sc-v2-picker__panel')
            ->setAttr('v-if', $hasItemsExpression);
        $panel->append(
            El::double('div')
                ->addClass('sc-v2-picker__summary')
                ->append(sprintf('已选 {{ %s }} 项', $countExpression))
        );

        $list = El::double('div')->addClass('sc-v2-picker__list');
        $item = El::double('div')->addClass('sc-v2-picker__item')->setAttrs([
            'v-for' => sprintf('(pickerItem, pickerIndex) in %s', $itemsExpression),
            ':key' => 'pickerItem.__pickerValue ?? pickerIndex',
        ]);
        $item->append(
            El::double('div')
                ->addClass('sc-v2-picker__item-text')
                ->setAttr(':title', $displayExpression)
                ->append(sprintf('{{ %s }}', $displayExpression))
        );
        $item->append(
            El::double('el-button')->setAttrs([
                'link' => '',
                'type' => 'danger',
                'icon' => 'CloseBold',
                ':disabled' => $disabledExpression,
                '@click' => $removeExpression,
            ])
        );
        $list->append($item);
        $panel->append($list);
        $root->append($panel);
        $root->append(
            El::double('div')
                ->addClass('sc-v2-picker__empty')
                ->setAttr('v-else', '')
                ->append($field->getEmptyText())
        );

        $actions = El::double('div')->addClass('sc-v2-picker__actions');
        $actions->append(
            El::double('el-button')->setAttrs([
                'type' => 'primary',
                'icon' => 'Plus',
                ':disabled' => $disabledExpression,
                '@click' => $openExpression,
            ])->append($field->getButtonLabel())
        );

        if ($field->isClearable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'icon' => 'Delete',
                    'v-if' => $hasItemsExpression,
                    ':disabled' => $disabledExpression,
                    '@click' => $clearExpression,
                ])->append('清空')
            );
        }

        $root->append($actions);

        return $root;
    }

    private function applyUploadFieldBehavior(
        AbstractHtmlElement $component,
        string $fieldPath,
        Field $field,
        ?UploadField $uploadField,
        FormRenderOptions $options,
        ?string $fieldPathExpression = null
    ): void {
        if ($field->type() !== FieldType::UPLOAD || $uploadField === null) {
            return;
        }

        $upload = $uploadField->getUpload();

        if ($options->hasUploadContext()) {
            $component->setAttr(
                ':file-list',
                $fieldPathExpression === null
                    ? $options->uploadFileListExpression($fieldPath)
                    : $options->uploadFileListExpressionByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                '@update:file-list',
                $fieldPathExpression === null
                    ? $options->uploadFileListUpdateHandler($fieldPath)
                    : $options->uploadFileListUpdateHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':before-upload',
                $fieldPathExpression === null
                    ? $options->uploadBeforeHandler($fieldPath)
                    : $options->uploadBeforeHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':on-success',
                $fieldPathExpression === null
                    ? $options->uploadSuccessHandler($fieldPath)
                    : $options->uploadSuccessHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':on-error',
                $fieldPathExpression === null
                    ? $options->uploadErrorHandler($fieldPath)
                    : $options->uploadErrorHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':on-remove',
                $fieldPathExpression === null
                    ? $options->uploadRemoveHandler($fieldPath)
                    : $options->uploadRemoveHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':on-exceed',
                $fieldPathExpression === null
                    ? $options->uploadExceedHandler($fieldPath)
                    : $options->uploadExceedHandlerByPathExpression($fieldPathExpression)
            );
            $component->setAttr(':on-preview', $options->uploadPreviewMethod);
        }

        if (($upload['kind'] ?? 'file') === 'image') {
            $component->append(
                El::double('el-icon')->append(
                    El::double('Plus')
                )
            );
        } else {
            $component->append(
                El::double('el-button')->setAttr('type', 'primary')->append(
                    (string)($upload['buttonText'] ?? '选择文件')
                )
            );
        }

        if (!empty($upload['tip'])) {
            $component->append(
                El::double('template')->setAttr('#tip')->append(
                    El::double('div')->addClass('el-upload__tip')->append((string)$upload['tip'])
                )
            );
        }
    }

    private function wrapFieldRoot(Field $field, DoubleLabel $item, bool $inline, ?string $visibleWhen): AbstractHtmlElement
    {
        $root = $inline
            ? $item
            : El::double('el-col')->setAttr(':span', $field->getSpan())->append($item);

        if ($visibleWhen !== null) {
            $root->setAttr('v-if', $visibleWhen);
        }

        return $root;
    }

    private function appendHelpText(DoubleLabel $item, Field $field): void
    {
        if (!$field->getHelpText()) {
            return;
        }

        $item->append(
            El::double('div')->addClass('sc-v2-form__help')->append($field->getHelpText())
        );
    }

    private function appendOptionChildren(
        AbstractHtmlElement $component,
        OptionField $field,
        ?string $optionsExpression,
        string $optionTag
    ): void {
        $itemsExpression = $optionsExpression ?? $this->jsValue($field->getOptions());
        $option = $this->buildChoiceOptionElement($optionTag);
        $option->setAttrs([
            'v-for' => sprintf('(item, index) in %s', $itemsExpression),
            ':key' => 'item.value ?? index',
            ':disabled' => 'item.disabled === true',
        ]);

        if ($optionTag === 'el-option') {
            $option->setAttr(':label', 'item.label');
            $option->setAttr(':value', 'item.value');
        } else {
            $option->setAttr(':label', 'item.value');
            $option->setAttr(':value', 'item.value');
            $option->append('{{ item.label }}');
        }

        $component->append($option);
    }

    private function buildChoiceOptionElement(
        string $optionTag,
    ): AbstractHtmlElement {
        return El::double($optionTag);
    }

    private function wrapFieldControl(
        Field $field,
        AbstractHtmlElement $component,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $control = El::double('div')->addClass('sc-v2-form__control')->append($component);

        if (!$field->hasSuffix()) {
            return $control;
        }

        $suffix = El::double('div')->addClass('sc-v2-form__suffix');

        $suffixContent = $field->getSuffixContent();
        if ($suffixContent instanceof AbstractHtmlElement) {
            $suffix->append($suffixContent);
        } elseif (is_string($suffixContent) && $suffixContent !== '') {
            $suffix->append(
                El::double('span')->addClass('sc-v2-form__suffix-text')->append($suffixContent)
            );
        }

        foreach ($field->getSuffixActions() as $action) {
            $suffix->append($this->actionButtonRenderer->render($action, false, 'small', null, $renderContext));
        }

        $control->append($suffix);

        return $control;
    }

    private function ensureEditorAssets(RenderContext $renderContext): void
    {
        $assets = $renderContext->document()->assets();
        $assets->addStylesheet(StaticResource::SCEDITOR_CSS);
        $assets->addScript(StaticResource::SCEDITOR_JS);
    }
}
