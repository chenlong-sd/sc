<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

final class FieldRenderer
{
    use BuildsJsExpressions;

    public function __construct(
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function render(
        Field $field,
        string $modelName,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options
    ): AbstractHtmlElement
    {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
        );
    }

    public function renderScoped(
        Field $field,
        string $modelName,
        string $propExpression,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options
    ): AbstractHtmlElement {
        return $this->renderField(
            field: $field,
            modelName: $modelName,
            fieldPath: $fieldPath,
            inline: $inline,
            options: $options,
            propExpression: $propExpression,
        );
    }

    public function renderTableCell(
        Field $field,
        string $fieldModelName,
        string $fieldPath,
        string $propExpression,
        FormRenderOptions $options
    ): AbstractHtmlElement {
        return $this->renderField(
            field: $field,
            modelName: $fieldModelName,
            fieldPath: $fieldPath,
            inline: false,
            options: $options,
            propExpression: $propExpression,
            tableCell: true,
        );
    }

    private function renderField(
        Field $field,
        string $modelName,
        string $fieldPath,
        bool $inline,
        FormRenderOptions $options,
        ?string $propExpression = null,
        bool $tableCell = false
    ): AbstractHtmlElement {
        if ($field->type() === FieldType::HIDDEN) {
            return El::fictitious();
        }

        $modelAccessor = $this->jsModelAccessor($modelName, $field->name());
        $visibleWhen = $this->normalizeFieldExpression($field->getVisibleWhen(), $modelName);
        $disabledWhen = $this->normalizeFieldExpression($field->getDisabledWhen(), $modelName);
        $optionField = $field instanceof OptionField ? $field : null;
        $uploadField = $field instanceof UploadField ? $field : null;
        $placeholderField = $field instanceof PlaceholderFieldInterface ? $field : null;
        $validatableField = $field instanceof ValidatableFieldInterface ? $field : null;
        $hasRemoteOptions = $optionField?->hasRemoteOptions() && $options->hasRemoteOptionsContext();

        $item = $this->buildRenderItem($field, $fieldPath, $validatableField, $propExpression, $tableCell);
        $component = $this->buildFieldComponent(
            $field,
            $modelAccessor,
            $inline,
            $placeholderField,
            $uploadField
        );

        $this->applyFieldProps($component, $field);
        $this->applyDisabledState($component, $field, $disabledWhen);
        $this->applyOptionFieldBehavior($component, $field, $fieldPath, $optionField, $hasRemoteOptions, $options, $propExpression);
        $this->applyUploadFieldBehavior($component, $fieldPath, $field, $uploadField, $options, $propExpression);

        $item->append($this->wrapFieldControl($field, $component));
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
        ?ValidatableFieldInterface $validatableField
    ): DoubleLabel
    {
        $item = El::double('el-form-item')
            ->setAttr('label', $field->label())
            ->setAttr('prop', $fieldPath);

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
        bool $tableCell
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
                ->setAttr('label', $field->label())
                ->setAttr(':prop', $propExpression);

            if ($validatableField?->isRequired()) {
                $item->setAttr('required');
            }

            return $item;
        }

        return $this->buildFieldItem($field, $fieldPath, $validatableField);
    }

    private function buildFieldComponent(
        Field $field,
        string $modelAccessor,
        bool $inline,
        ?PlaceholderFieldInterface $placeholderField,
        ?UploadField $uploadField
    ): AbstractHtmlElement {
        $placeholder = $placeholderField?->getPlaceholder() ?? '';
        $upload = $uploadField?->getUpload() ?? [];

        return match ($field->type()) {
            FieldType::TEXT => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholder,
                'clearable' => '',
            ]),
            FieldType::PASSWORD => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'password',
                'placeholder' => $placeholder,
                'clearable' => '',
            ]),
            FieldType::TEXTAREA => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'textarea',
                ':rows' => (string)($field->getProps()['rows'] ?? 4),
                'placeholder' => $placeholder,
            ]),
            FieldType::NUMBER => El::double('el-input-number')->setAttrs([
                'v-model' => $modelAccessor,
                'style' => $inline ? 'width: 180px' : 'width: 100%',
            ]),
            FieldType::SELECT => El::double('el-select')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'min-width: 180px' : 'width: 100%',
            ]),
            FieldType::RADIO => El::double('el-radio-group')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            FieldType::CHECKBOX => El::double('el-checkbox-group')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            FieldType::CASCADER => El::double('el-cascader')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'min-width: 220px' : 'width: 100%',
            ]),
            FieldType::DATE => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'date',
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 220px' : 'width: 100%',
            ]),
            FieldType::DATETIME => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'datetime',
                'placeholder' => $placeholder,
                'clearable' => '',
                'style' => $inline ? 'width: 240px' : 'width: 100%',
            ]),
            FieldType::DATE_RANGE => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'daterange',
                'range-separator' => (string)($field->getProps()['range-separator'] ?? '至'),
                'start-placeholder' => (string)($field->getProps()['start-placeholder'] ?? '开始日期'),
                'end-placeholder' => (string)($field->getProps()['end-placeholder'] ?? '结束日期'),
                'clearable' => '',
                'style' => $inline ? 'width: 320px' : 'width: 100%',
            ]),
            FieldType::UPLOAD => El::double('el-upload')->setAttrs(array_filter([
                'action' => (string)($upload['action'] ?? ''),
                'method' => (string)($upload['method'] ?? 'post'),
                'name' => (string)($upload['name'] ?? 'file'),
                ':headers' => JsonExpressionEncoder::encode($upload['headers'] ?? []),
                ':data' => JsonExpressionEncoder::encode($upload['data'] ?? []),
                ':multiple' => ($upload['multiple'] ?? false) ? 'true' : 'false',
                ':limit' => isset($upload['limit']) ? (string)$upload['limit'] : null,
                ':show-file-list' => 'true',
                'list-type' => (string)($upload['listType'] ?? 'text'),
                'accept' => $upload['accept'] ?? '',
            ], static fn(mixed $value) => $value !== null && $value !== '')),
            FieldType::SWITCH => El::double('el-switch')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            default => El::fictitious(),
        };
    }

    private function applyFieldProps(AbstractHtmlElement $component, Field $field): void
    {
        foreach ($field->getProps() as $attr => $value) {
            if ($field->type() === FieldType::TEXTAREA && $attr === 'rows') {
                continue;
            }
            if ($field->type() === FieldType::DATE_RANGE && in_array($attr, ['range-separator', 'start-placeholder', 'end-placeholder'], true)) {
                continue;
            }

            $component->setAttr($attr, (string)$value);
        }
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

        $optionsExpression = $hasRemoteOptions
            ? ($fieldPathExpression === null
                ? $options->remoteOptionsExpression($fieldPath)
                : $options->remoteOptionsExpressionByPathExpression($fieldPathExpression))
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
                $hasRemoteOptions
                    ? ($fieldPathExpression === null
                        ? $options->remoteOptionsExpression($fieldPath)
                        : $options->remoteOptionsExpressionByPathExpression($fieldPathExpression))
                    : JsonExpressionEncoder::encode($optionField->getOptions())
            );
        }
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
                'v-model:file-list',
                $fieldPathExpression === null
                    ? $options->uploadFileListExpression($fieldPath)
                    : $options->uploadFileListExpressionByPathExpression($fieldPathExpression)
            );
            $component->setAttr(
                ':on-success',
                $fieldPathExpression === null
                    ? $options->uploadSuccessHandler($fieldPath)
                    : $options->uploadSuccessHandlerByPathExpression($fieldPathExpression)
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
        if ($optionsExpression !== null) {
            $option = $this->buildChoiceOptionElement($optionTag, true);
            $option->setAttrs([
                'v-for' => sprintf('(item, index) in %s', $optionsExpression),
                ':key' => 'item.value ?? index',
                ':disabled' => 'item.disabled === true',
            ]);

            if ($optionTag === 'el-option') {
                $option->setAttr(':label', 'item.label');
                $option->setAttr(':value', 'item.value');
            } else {
                $option->setAttr(':label', 'item.value');
                $option->setAttr(':value', 'item.value');
            }

            $component->append($option);

            return;
        }

        foreach ($field->getOptions() as $option) {
            $component->append($this->buildChoiceOptionElement($optionTag, false, $option));
        }
    }

    private function buildChoiceOptionElement(
        string $optionTag,
        bool $remote,
        array $option = []
    ): AbstractHtmlElement {
        $element = El::double($optionTag);

        if ($remote) {
            if ($optionTag !== 'el-option') {
                $element->append('{{ item.label }}');
            }

            return $element;
        }

        $value = $this->jsLiteral($option['value'] ?? '');
        $label = (string)($option['label'] ?? '');

        if (($option['disabled'] ?? false) === true) {
            $element->setAttr('disabled');
        }

        if ($optionTag === 'el-option') {
            $element->setAttrs([
                'label' => $label,
                ':value' => $value,
            ]);

            return $element;
        }

        $element->setAttrs([
            ':label' => $value,
            ':value' => $value,
        ])->append($label);

        return $element;
    }

    private function wrapFieldControl(Field $field, AbstractHtmlElement $component): AbstractHtmlElement
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
            $suffix->append($this->actionButtonRenderer->render($action, false, 'small'));
        }

        $control->append($suffix);

        return $control;
    }
}
