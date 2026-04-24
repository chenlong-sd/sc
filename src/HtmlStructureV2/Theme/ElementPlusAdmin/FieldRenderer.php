<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Fields\CascaderField;
use Sc\Util\HtmlStructureV2\Components\Fields\DateField;
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
        ?RenderContext $renderContext = null,
        bool $formReadonly = false,
        ?string $containerLabelWidth = null
    ): AbstractHtmlElement
    {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
            renderContext: $renderContext,
            formReadonly: $formReadonly,
            containerLabelWidth: $containerLabelWidth,
        );
    }

    public function renderScoped(
        Field $field,
        string $modelName,
        string $propExpression,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        bool $formReadonly = false,
        ?string $containerLabelWidth = null
    ): AbstractHtmlElement {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
            propExpression: $propExpression,
            renderContext: $renderContext,
            formReadonly: $formReadonly,
            containerLabelWidth: $containerLabelWidth,
        );
    }

    public function renderTableCell(
        Field $field,
        string $fieldModelName,
        string $fieldPath,
        string $propExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        bool $formReadonly = false
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
            formReadonly: $formReadonly,
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
        ?RenderContext $renderContext = null,
        bool $formReadonly = false,
        ?string $containerLabelWidth = null
    ): AbstractHtmlElement {
        if ($field->type() === FieldType::HIDDEN) {
            return El::fictitious();
        }

        $modelAccessor = $this->jsModelAccessor($modelName, $field->name());
        $visibleWhen = $this->normalizeFieldExpression($field->getVisibleWhen(), $modelName);
        $disabledWhen = $this->normalizeFieldExpression($field->getDisabledWhen(), $modelName);
        $readonlyWhen = $this->normalizeFieldExpression($field->getReadonlyWhen(), $modelName);
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
            $options->showLabels && $field->hasLabel(),
            $containerLabelWidth
        );
        if ($pickerField !== null) {
            $component = $this->buildPickerComponent(
                $pickerField,
                $fieldPath,
                $options,
                $propExpression,
                $disabledWhen,
                $readonlyWhen,
                $formReadonly
            );
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
            $this->applyInteractivityState($component, $field, $disabledWhen, $readonlyWhen, $formReadonly);
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
        bool $showLabels,
        ?string $containerLabelWidth = null
    ): DoubleLabel
    {
        $item = El::double('el-form-item')
            ->setAttr('prop', $fieldPath);

        if ($showLabels) {
            $item->setAttr('label', $field->label());
        } else {
            $item->setAttr('label-width', '0');
        }

        $effectiveLabelWidth = $this->resolveEffectiveLabelWidth($field, $containerLabelWidth);
        if ($showLabels && $effectiveLabelWidth !== null) {
            $item->setAttr('label-width', $effectiveLabelWidth);
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
        bool $showLabels,
        ?string $containerLabelWidth = null
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

            $effectiveLabelWidth = $this->resolveEffectiveLabelWidth($field, $containerLabelWidth);
            if ($showLabels && $effectiveLabelWidth !== null) {
                $item->setAttr('label-width', $effectiveLabelWidth);
            }

            if ($validatableField?->isRequired()) {
                $item->setAttr('required');
            }

            return $item;
        }

        return $this->buildFieldItem($field, $fieldPath, $validatableField, $showLabels, $containerLabelWidth);
    }

    private function resolveEffectiveLabelWidth(Field $field, ?string $containerLabelWidth): ?string
    {
        return $field->getLabelWidth() ?? $containerLabelWidth;
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
            FieldType::DATE,
            FieldType::DATETIME,
            FieldType::DATE_RANGE => $this->buildDateFieldComponent($field, $bindModelValue, $placeholder, $inline),
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
            if ($field instanceof DateField && $attr === 'type') {
                continue;
            }
            if (
                $field instanceof DateField
                && $field->isRangePicker()
                && in_array($attr, ['range-separator', 'start-placeholder', 'end-placeholder'], true)
            ) {
                continue;
            }

            $props[$attr] = $value;
        }

        $this->applyRenderableAttributes($component, $props);
    }

    private function applyInteractivityState(
        AbstractHtmlElement $component,
        Field $field,
        ?string $disabledWhen,
        ?string $readonlyWhen,
        bool $formReadonly
    ): void
    {
        $supportsReadonly = $this->supportsReadonly($field);
        $readonlyExpression = $supportsReadonly
            ? $this->resolveBooleanStateExpression($formReadonly || $field->isReadonly(), $readonlyWhen)
            : null;
        $disabledExpression = $this->resolveBooleanStateExpression(
            $field->isDisabled() || (!$supportsReadonly && ($formReadonly || $field->isReadonly())),
            $disabledWhen,
            !$supportsReadonly ? $readonlyWhen : null
        );

        $this->applyBooleanAttribute($component, 'readonly', $readonlyExpression);
        $this->applyBooleanAttribute($component, 'disabled', $disabledExpression);
    }

    private function supportsReadonly(Field $field): bool
    {
        return match ($field->type()) {
            FieldType::TEXT,
            FieldType::PASSWORD,
            FieldType::TEXTAREA,
            FieldType::DATE,
            FieldType::DATETIME,
            FieldType::DATE_RANGE => true,
            default => false,
        };
    }

    private function buildDateFieldComponent(
        Field $field,
        callable $bindModelValue,
        string $placeholder,
        bool $inline
    ): AbstractHtmlElement {
        $pickerType = $field instanceof DateField
            ? $field->getPickerType()
            : match ($field->type()) {
                FieldType::DATE => 'date',
                FieldType::DATE_RANGE => 'daterange',
                default => 'datetime',
            };

        $baseAttrs = [
            'clearable' => '',
            'style' => $this->resolveDateFieldInlineStyle($pickerType, $inline),
        ];

        if ($field instanceof DateField && $field->usesTimePicker()) {
            return El::double('el-time-picker')->setAttrs($bindModelValue(array_merge($baseAttrs, [
                'placeholder' => $placeholder,
            ])));
        }

        if ($field instanceof DateField && $field->isRangePicker()) {
            $defaultStartPlaceholder = $pickerType === 'datetimerange' ? '开始时间' : '开始日期';
            $defaultEndPlaceholder = $pickerType === 'datetimerange' ? '结束时间' : '结束日期';
            $defaultTimeExpression = $this->resolveRangeDefaultTimeExpression($field, $pickerType);

            return El::double('el-date-picker')->setAttrs($bindModelValue(array_filter(array_merge($baseAttrs, [
                'type' => $pickerType,
                'range-separator' => (string)($field->getProps()['range-separator'] ?? '至'),
                'start-placeholder' => (string)($field->getProps()['start-placeholder'] ?? $defaultStartPlaceholder),
                'end-placeholder' => (string)($field->getProps()['end-placeholder'] ?? $defaultEndPlaceholder),
                ':default-time' => $defaultTimeExpression,
            ]), static fn(mixed $value): bool => $value !== null)));
        }

        return El::double('el-date-picker')->setAttrs($bindModelValue(array_merge($baseAttrs, [
            'type' => $pickerType,
            'placeholder' => $placeholder,
        ])));
    }

    private function resolveDateFieldInlineStyle(string $pickerType, bool $inline): string
    {
        if (!$inline) {
            return 'width: 100%';
        }

        return match ($pickerType) {
            'datetime' => 'width: 240px',
            'datetimerange' => 'width: 360px',
            'daterange', 'monthrange' => 'width: 320px',
            'time' => 'width: 220px',
            default => 'width: 220px',
        };
    }

    private function resolveRangeDefaultTimeExpression(Field $field, string $pickerType): ?string
    {
        if ($pickerType !== 'datetimerange') {
            return null;
        }

        $props = $field->getProps();
        if (array_key_exists(':default-time', $props) || array_key_exists('default-time', $props)) {
            return null;
        }

        return '[new Date(2000, 0, 1, 0, 0, 0), new Date(2000, 0, 1, 23, 59, 59)]';
    }

    private function resolveBooleanStateExpression(bool $staticEnabled, ?string ...$dynamicExpressions): ?string
    {
        if ($staticEnabled) {
            return 'true';
        }

        $conditions = array_values(array_filter(
            $dynamicExpressions,
            static fn(?string $expression): bool => is_string($expression) && trim($expression) !== ''
        ));

        if ($conditions === []) {
            return null;
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return sprintf('(%s)', implode(') || (', $conditions));
    }

    private function applyBooleanAttribute(AbstractHtmlElement $component, string $attribute, ?string $expression): void
    {
        if ($expression === null) {
            return;
        }

        if ($expression === 'true') {
            $component->setAttr($attribute);

            return;
        }

        $component->setAttr(':' . $attribute, $expression);
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
        ?string $disabledWhen = null,
        ?string $readonlyWhen = null,
        bool $formReadonly = false
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
        $disabledExpression = $this->resolveBooleanStateExpression(
            $field->isDisabled() || $formReadonly || $field->isReadonly(),
            $disabledWhen,
            $readonlyWhen
        ) ?? 'false';

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
        $kind = $upload['kind'] ?? 'file';
        $listType = (string)($upload['listType'] ?? 'text');
        $showProgress = ($upload['showProgress'] ?? false) && $kind !== 'image';
        $fileListExpression = null;

        $component->addClass('sc-v2-upload-field');

        if ($options->hasUploadContext()) {
            $fileListExpression = $fieldPathExpression === null
                ? $options->uploadFileListExpression($fieldPath)
                : $options->uploadFileListExpressionByPathExpression($fieldPathExpression);
            $component->setAttr(
                ':file-list',
                $fileListExpression
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
            if ($showProgress) {
                $component->setAttr(
                    ':on-progress',
                    $fieldPathExpression === null
                        ? $options->uploadProgressHandler($fieldPath)
                        : $options->uploadProgressHandlerByPathExpression($fieldPathExpression)
                );
            }
            $component->setAttr(':on-preview', $options->uploadPreviewMethod);
        }

        if ($showProgress) {
            $component->addClass('sc-v2-upload-show-progress');
        }
        if ($kind === 'file' && $listType === 'text') {
            $component->addClass('sc-v2-upload-kind-file');
            $deleteHandler = null;
            if ($fileListExpression !== null) {
                $component->addClass('sc-v2-upload-kind-file--custom-remove');
                $updateHandler = $fieldPathExpression === null
                    ? $options->uploadFileListUpdateHandler($fieldPath)
                    : $options->uploadFileListUpdateHandlerByPathExpression($fieldPathExpression);
                $deleteHandler = sprintf(
                    "(event) => { event.preventDefault(); event.stopPropagation(); (%s)(((%s) || []).filter((item) => ((item.uid || item.url || item.name || '') !== (file.uid || file.url || file.name || '')))); }",
                    $updateHandler,
                    $fileListExpression
                );
            }
            $component->append($this->buildUploadFileSlot($options, $showProgress, $deleteHandler));
        }
        if ($kind === 'image' || $kind === 'video') {
            $isSingle = !($upload['multiple'] ?? false);
            if ($isSingle && $fileListExpression !== null) {
                $component->addClass('sc-v2-upload-single-media');
                $component->setAttr(
                    ':class',
                    sprintf("{ 'sc-v2-upload-single-media--filled': (%s).length > 0 }", $fileListExpression)
                );
            }
            if ($kind === 'video') {
                $component->addClass('sc-v2-upload-kind-video');
            }
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

    private function buildUploadFileSlot(
        FormRenderOptions $options,
        bool $showProgress,
        ?string $deleteHandler = null
    ): AbstractHtmlElement
    {
        $actions = El::double('div')->addClass('sc-v2-upload-file-item__actions')->append(
            El::double('el-link')->setAttrs([
                'v-if' => "file.status === 'success' && isUploadPreviewableFile(file)",
                'type' => 'primary',
                ':underline' => 'false',
                '@click' => sprintf("(event) => { event.preventDefault(); %s(file); }", $options->uploadPreviewMethod),
            ])->append('预览'),
            El::double('el-link')->setAttrs([
                'v-if' => "file.status === 'success' && resolveUploadFileUrl(file)",
                'type' => 'primary',
                ':underline' => 'false',
                ':href' => 'resolveUploadFileUrl(file)',
                ':download' => "file.name || ''",
                'target' => '_blank',
            ])->append('下载')
        );
        if ($deleteHandler !== null) {
            $actions->append(
                El::double('el-link')->setAttrs([
                    'v-if' => "file.status === 'success'",
                    'type' => 'danger',
                    ':underline' => 'false',
                    '@click' => $deleteHandler,
                ])->append('删除')
            );
        }

        $template = El::double('template')->setAttr('#file', '{ file }');
        $item = El::double('div')->addClass('sc-v2-upload-file-item')->append(
            El::double('div')->addClass('sc-v2-upload-file-item__main')->append(
                El::double('div')->addClass('sc-v2-upload-file-item__name')->setAttr(':title', "file.name || ''")->append(
                    El::double('el-icon')->addClass('sc-v2-upload-file-item__icon')->append(
                        El::double('Document')
                    ),
                    El::double('span')->addClass('sc-v2-upload-file-item__name-text')->append('{{ file.name || "未命名文件" }}')
                ),
                $actions
            )
        );

        if ($showProgress) {
            $item->append(
                El::double('div')->addClass('sc-v2-upload-file-item__progress')->setAttr('v-if', "file.status === 'uploading'")->append(
                    El::double('div')->addClass('sc-v2-upload-file-item__progress-track')->append(
                        El::double('div')->addClass('sc-v2-upload-file-item__progress-fill')->setAttr(
                            ':style',
                            "{ width: Math.max(0, Math.min(100, Number(file.percentage || 0))) + '%' }"
                        )
                    ),
                    El::double('span')->addClass('sc-v2-upload-file-item__progress-text')
                        ->append('{{ Math.round(Math.max(0, Math.min(100, Number(file.percentage || 0)))) }}%')
                )
            );
        }

        $template->append($item);

        return $template;
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
