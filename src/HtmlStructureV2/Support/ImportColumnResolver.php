<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;

final class ImportColumnResolver
{
    public function __construct(
        private readonly RenderableComponentWalker $renderableComponentWalker = new RenderableComponentWalker(),
    ) {
    }

    public function fromForm(Form $form, array $overrides = []): array
    {
        return array_replace(
            $this->extractColumnsFromForm($form),
            $this->normalizeOverrides($overrides)
        );
    }

    public function fromPage(AbstractPage $page, ?string $formKey = null, array $overrides = []): array
    {
        return $this->fromForm(
            $this->resolveFormFromPage($page, $formKey),
            $overrides
        );
    }

    public function fromDialog(
        Dialog $dialog,
        Form|AbstractPage|null $iframeSource = null,
        ?string $formKey = null,
        array $overrides = []
    ): array {
        if ($dialog->getForm() instanceof Form) {
            return $this->fromForm($dialog->getForm(), $overrides);
        }

        if ($iframeSource instanceof Form) {
            return $this->fromForm($iframeSource, $overrides);
        }

        if ($iframeSource instanceof AbstractPage) {
            return $this->fromPage($iframeSource, $formKey, $overrides);
        }

        if ($dialog->bodyType() === 'iframe') {
            throw new InvalidArgumentException(sprintf(
                'Dialog [%s] uses iframe body; please pass the iframe child Form/Page explicitly when resolving import columns.',
                $dialog->key()
            ));
        }

        throw new InvalidArgumentException(sprintf(
            'Dialog [%s] does not contain a resolvable form source for import columns.',
            $dialog->key()
        ));
    }

    private function resolveFormFromPage(AbstractPage $page, ?string $formKey = null): Form
    {
        $forms = $this->collectFormsFromPage($page);
        if ($forms === []) {
            throw new InvalidArgumentException(sprintf(
                'Page [%s] does not contain any resolvable V2 Form section.',
                $page->key()
            ));
        }

        $normalizedFormKey = is_string($formKey) ? trim($formKey) : null;
        if ($normalizedFormKey !== '' && $normalizedFormKey !== null) {
            $resolved = $forms[$normalizedFormKey] ?? null;
            if ($resolved instanceof Form) {
                return $resolved;
            }

            throw new InvalidArgumentException(sprintf(
                'Page [%s] does not contain form key [%s].',
                $page->key(),
                $normalizedFormKey
            ));
        }

        if (count($forms) > 1) {
            throw new InvalidArgumentException(sprintf(
                'Page [%s] contains multiple forms; please pass an explicit form key to importColumnsFromPage(..., $formKey).',
                $page->key()
            ));
        }

        return array_values($forms)[0];
    }

    /**
     * @return array<string, Form>
     */
    private function collectFormsFromPage(AbstractPage $page): array
    {
        $forms = [];
        $this->renderableComponentWalker->walk(
            $page->getSections(),
            static function (Renderable $component) use (&$forms): void {
                if ($component instanceof Form) {
                    $forms[$component->key()] = $component;
                }
            }
        );

        return $forms;
    }

    private function extractColumnsFromForm(Form $form): array
    {
        $columns = [];

        foreach ($form->schema()->fields() as $fieldSchema) {
            $field = $fieldSchema->field();
            if ($fieldSchema->parentPath() !== null) {
                continue;
            }

            $column = $this->buildColumnForField($field);
            if ($column === null) {
                continue;
            }

            $columns[$field->name()] = $column;
        }

        return $columns;
    }

    private function buildColumnForField(Field $field): ?array
    {
        if (!$this->isImportableField($field)) {
            return null;
        }

        $title = $field->hasLabel() ? $field->label() : $field->name();
        $description = $this->generateFieldDescription($field);
        $requiredWhen = $this->resolveRequiredWhen($field);

        $column = [
            'title' => $title,
            'description' => $description,
            'required' => $this->isUnconditionallyRequired($field),
        ];
        if ($requiredWhen !== null) {
            $column['required_when'] = $requiredWhen;
        }

        if ($field instanceof OptionField && $field->getOptions() !== []) {
            $column['options'] = $this->normalizeOptions($field->getOptions());

            return $column;
        }

        if ($field->type() === FieldType::SWITCH) {
            $switchOptions = $this->resolveSwitchOptions($field);
            if ($switchOptions !== null) {
                $column['options'] = $switchOptions;

                return $column;
            }
        }

        return $column;
    }

    private function generateFieldDescription(Field $field): string
    {
        $parts = [];

        if ($this->isUnconditionallyRequired($field)) {
            $parts[] = '必填';
        } elseif ($this->isConditionallyRequired($field)) {
            $parts[] = '条件必填';
        }

        $rules = $field instanceof ValidatableFieldInterface ? $field->getRules() : [];
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                if (isset($rule['min'])) {
                    $parts[] = '最少' . $rule['min'] . '字';
                }
                if (isset($rule['max'])) {
                    $parts[] = '最多' . $rule['max'] . '字';
                }
                if (isset($rule['type']) && in_array($rule['type'], ['number', 'integer', 'float'], true)) {
                    $parts[] = '数值';
                }
            }
        }

        return implode('，', $parts);
    }

    private function isUnconditionallyRequired(Field $field): bool
    {
        return $field instanceof ValidatableFieldInterface
            && $field->isRequired()
            && !$field->isConditionalRequired();
    }

    private function isConditionallyRequired(Field $field): bool
    {
        return $field instanceof ValidatableFieldInterface
            && $field->isConditionalRequired();
    }

    private function resolveRequiredWhen(Field $field): ?string
    {
        if (!$field instanceof ValidatableFieldInterface) {
            return null;
        }

        $condition = trim((string) $field->getRequiredCondition());

        return $condition !== '' ? $condition : null;
    }

    private function isImportableField(Field $field): bool
    {
        if ($field->isDisabled()) {
            return false;
        }

        return match ($field->type()) {
            FieldType::TEXT,
            FieldType::TEXTAREA,
            FieldType::NUMBER,
            FieldType::SELECT,
            FieldType::RADIO,
            FieldType::DATE,
            FieldType::DATETIME,
            FieldType::SWITCH => true,
            default => false,
        };
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            if (!array_key_exists('value', $option) || !array_key_exists('label', $option)) {
                continue;
            }

            $normalizedOption = [
                'value' => $option['value'],
                'label' => $option['label'],
            ];
            foreach ($option as $key => $value) {
                if (!is_string($key) || $key === '' || $key === 'value' || $key === 'label') {
                    continue;
                }
                if (is_scalar($value) || $value === null) {
                    $normalizedOption[$key] = $value;
                }
            }

            $normalized[] = $normalizedOption;
        }

        return $normalized;
    }

    private function resolveSwitchOptions(Field $field): ?array
    {
        $props = $field->getProps();
        $activeValue = $props['active-value'] ?? $props[':active-value'] ?? 1;
        $inactiveValue = $props['inactive-value'] ?? $props[':inactive-value'] ?? 0;
        $activeLabel = $props['active-text'] ?? '开启';
        $inactiveLabel = $props['inactive-text'] ?? '关闭';

        if (!is_scalar($activeValue) || !is_scalar($inactiveValue)) {
            return null;
        }

        if (!is_scalar($activeLabel) || !is_scalar($inactiveLabel)) {
            return null;
        }

        return [
            [
                'value' => $activeValue,
                'label' => (string) $activeLabel,
            ],
            [
                'value' => $inactiveValue,
                'label' => (string) $inactiveLabel,
            ],
        ];
    }

    private function normalizeOverrides(array $overrides): array
    {
        $normalized = [];

        foreach ($overrides as $field => $config) {
            if (!is_string($field) || trim($field) === '') {
                continue;
            }

            if (!is_array($config) && !is_string($config)) {
                continue;
            }

            $normalized[$field] = $config;
        }

        return $normalized;
    }
}
