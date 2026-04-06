<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\PickerField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;

final class FormSchemaWalker
{
    public function __construct(
        private readonly FormNodePathWalker $formNodePathWalker = new FormNodePathWalker(),
    ) {
    }

    /** @var FormFieldSchema[] */
    private array $fields = [];
    private array $defaults = [];
    private array $pickerDefaults = [];
    private array $rules = [];
    private array $remoteOptions = [];
    private array $selectOptions = [];
    private array $pickers = [];
    private array $linkages = [];
    private array $uploads = [];
    private array $pickerPaths = [];
    private array $remoteOptionPaths = [];
    private array $uploadPaths = [];
    /** @var FormArrayGroupSchema[] */
    private array $arrayGroups = [];

    /**
     * @param FormNode[] $nodes
     */
    public function build(array $nodes): FormSchema
    {
        $this->reset();
        $this->formNodePathWalker->walk(
            $nodes,
            function (FormNode $node, FormNodePathContext $context): void {
                if ($node instanceof Field) {
                    $this->collectField($node, $context);

                    return;
                }

                if ($node instanceof FormArrayGroup) {
                    $this->collectArrayGroup($node, $context);

                    return;
                }

                if ($node instanceof CustomNode) {
                    return;
                }
            }
        );

        return new FormSchema(
            fields: $this->fields,
            defaults: $this->defaults,
            pickerDefaults: $this->pickerDefaults,
            rules: $this->rules,
            remoteOptions: $this->remoteOptions,
            selectOptions: $this->selectOptions,
            pickers: $this->pickers,
            linkages: $this->linkages,
            uploads: $this->uploads,
            pickerPaths: array_values(array_unique($this->pickerPaths)),
            remoteOptionPaths: array_values(array_unique($this->remoteOptionPaths)),
            uploadPaths: array_values(array_unique($this->uploadPaths)),
            arrayGroups: $this->arrayGroups,
        );
    }

    private function reset(): void
    {
        $this->fields = [];
        $this->defaults = [];
        $this->pickerDefaults = [];
        $this->rules = [];
        $this->remoteOptions = [];
        $this->selectOptions = [];
        $this->pickers = [];
        $this->linkages = [];
        $this->uploads = [];
        $this->pickerPaths = [];
        $this->remoteOptionPaths = [];
        $this->uploadPaths = [];
        $this->arrayGroups = [];
    }

    private function collectField(Field $field, FormNodePathContext $context): void
    {
        $path = $context->fieldPath($field->name());
        $this->fields[] = new FormFieldSchema($field, $path);
        FormPath::set($this->defaults, $path, $field->getDefault());

        if ($field instanceof PickerField) {
            $this->pickerPaths[] = $path;
            FormPath::set($this->pickers, $path, $field->getPickerConfig());
            FormPath::set($this->pickerDefaults, $path, $field->getPickerItemsDefault());
        }

        if ($field instanceof ValidatableFieldInterface && $field->hasRules()) {
            FormPath::set($this->rules, $path, $field->getRules());
        }

        if ($field instanceof OptionField) {
            FormPath::set($this->selectOptions, $path, $field->getOptions());

            if ($field->hasRemoteOptions()) {
                $this->remoteOptionPaths[] = $path;
                FormPath::set(
                    $this->remoteOptions,
                    $path,
                    $this->normalizeRemoteOptions($field, $context->fieldPrefix())
                );
            }

            if ($field->hasLinkageUpdates()) {
                FormPath::set(
                    $this->linkages,
                    $path,
                    $this->normalizeLinkageConfig($field, $context->fieldPrefix())
                );
            }
        }

        if ($field instanceof UploadField && $field->hasUpload()) {
            $this->uploadPaths[] = $path;
            FormPath::set($this->uploads, $path, $field->getUpload());
        }
    }

    private function collectArrayGroup(FormArrayGroup $group, FormNodePathContext $context): void
    {
        $path = $context->fieldPath($group->name());
        $rowSchema = (new self($this->formNodePathWalker))->build($group->getChildren());
        FormPath::set($this->defaults, $path, $group->buildInitialRows($rowSchema->defaults()));
        FormPath::set($this->pickerDefaults, $path, $group->buildInitialRows($rowSchema->pickerDefaults()));
        $this->arrayGroups[] = new FormArrayGroupSchema($path, $group, $rowSchema);
    }

    private function normalizeRemoteOptions(OptionField $field, string $prefix): array
    {
        $config = $field->getRemoteOptions() ?? [];

        $config['dependencies'] = array_values(array_unique(array_map(
            fn(string $path) => FormPath::resolve($prefix, $path),
            array_values(array_filter(
                $config['dependencies'] ?? [],
                static fn(mixed $path) => is_string($path) && trim($path) !== ''
            ))
        )));

        $config['params'] = $this->normalizeModelTokens($config['params'] ?? [], $prefix);

        return $config;
    }

    private function normalizeLinkageConfig(OptionField $field, string $prefix): array
    {
        $config = $field->getLinkageConfig() ?? [];
        $updates = [];

        foreach (($config['updates'] ?? []) as $targetField => $valueTemplate) {
            if (!is_string($targetField) || trim($targetField) === '') {
                continue;
            }

            $updates[FormPath::resolve($prefix, $targetField)] = $this->normalizeLinkageTemplate(
                $valueTemplate,
                $prefix
            );
        }

        return [
            'updates' => $updates,
            'clearOnEmpty' => $config['clearOnEmpty'] ?? true,
        ];
    }

    private function normalizeModelTokens(mixed $value, string $prefix): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeModelTokens($item, $prefix);
            }

            return $normalized;
        }

        if (!is_string($value) || !str_starts_with($value, '@')) {
            return $value;
        }

        return '@' . FormPath::resolve($prefix, substr($value, 1));
    }

    private function normalizeLinkageTemplate(mixed $template, string $prefix): mixed
    {
        if (!is_string($template)) {
            return $template;
        }

        return preg_replace_callback(
            '/@model\.([A-Za-z0-9_.$-]+)/',
            static fn(array $matches) => '@model.' . FormPath::resolve($prefix, $matches[1] ?? ''),
            $template
        ) ?? $template;
    }
}
