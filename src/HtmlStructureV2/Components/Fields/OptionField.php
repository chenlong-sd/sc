<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSearch;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\SearchableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

class OptionField extends Field implements PlaceholderFieldInterface, SearchableFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasSearch;
    use HasValidation;

    protected array $options = [];
    protected ?array $remoteOptions = null;
    protected array $remoteOptionDependencies = [];
    protected bool $remoteOptionsClearOnChange = true;
    protected array $linkageUpdates = [];
    protected bool $linkageClearOnEmpty = true;

    public function __construct(string $name, string $label, FieldType $type = FieldType::SELECT)
    {
        parent::__construct($name, $label, $type);
    }

    public function options(array $options): static
    {
        $this->options = [];

        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label) && array_key_exists('label', $label)) {
                $this->options[] = $label;
                continue;
            }

            $this->options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $this;
    }

    public function remoteOptions(
        string $url,
        string $valueField = 'id',
        string $labelField = 'name',
        array $params = []
    ): static {
        $this->remoteOptions = [
            'url' => $url,
            'method' => 'get',
            'valueField' => $valueField,
            'labelField' => $labelField,
            'params' => $params,
        ];

        return $this;
    }

    public function remoteOptionsMethod(string $method): static
    {
        if ($this->remoteOptions === null) {
            $this->remoteOptions = [];
        }

        $this->remoteOptions['method'] = strtolower($method);

        return $this;
    }

    public function remoteOptionsDependsOn(string ...$fields): static
    {
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '' || in_array($field, $this->remoteOptionDependencies, true)) {
                continue;
            }

            $this->remoteOptionDependencies[] = $field;
        }

        return $this;
    }

    public function remoteOptionsClearOnChange(bool $clear = true): static
    {
        $this->remoteOptionsClearOnChange = $clear;

        return $this;
    }

    public function linkageUpdate(string $targetField, string|JsExpression $valueTemplate = '@label'): static
    {
        $this->linkageUpdates[$targetField] = $valueTemplate;

        return $this;
    }

    public function linkageUpdates(array $updates): static
    {
        foreach ($updates as $targetField => $valueTemplate) {
            if (!is_string($targetField)) {
                continue;
            }

            if (!is_string($valueTemplate) && !$valueTemplate instanceof JsExpression) {
                continue;
            }

            $this->linkageUpdate($targetField, $valueTemplate);
        }

        return $this;
    }

    public function linkageClearOnEmpty(bool $clear = true): static
    {
        $this->linkageClearOnEmpty = $clear;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasRemoteOptions(): bool
    {
        return $this->remoteOptions !== null;
    }

    public function getRemoteOptions(): ?array
    {
        if ($this->remoteOptions === null) {
            return null;
        }

        return array_merge($this->remoteOptions, [
            'dependencies' => $this->getRemoteOptionDependencies(),
            'clearOnChange' => $this->remoteOptionsClearOnChange,
        ]);
    }

    public function hasLinkageUpdates(): bool
    {
        return $this->linkageUpdates !== [];
    }

    public function getLinkageConfig(): ?array
    {
        if (!$this->hasLinkageUpdates()) {
            return null;
        }

        return [
            'updates' => $this->linkageUpdates,
            'clearOnEmpty' => $this->linkageClearOnEmpty,
        ];
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return $this->type() === FieldType::CHECKBOX ? [] : null;
    }

    protected function defaultPromptPrefix(): string
    {
        return '请选择';
    }

    protected function validationPromptPrefix(): string
    {
        return '请选择';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return 'change';
    }

    protected function defaultSearchType(): string
    {
        return $this->type() === FieldType::CHECKBOX ? 'IN' : '=';
    }

    private function getRemoteOptionDependencies(): array
    {
        $dependencies = $this->remoteOptionDependencies;

        foreach (($this->remoteOptions['params'] ?? []) as $value) {
            $this->collectRemoteDependenciesFromValue($value, $dependencies);
        }

        return array_values(array_unique($dependencies));
    }

    private function collectRemoteDependenciesFromValue(mixed $value, array &$dependencies): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectRemoteDependenciesFromValue($item, $dependencies);
            }

            return;
        }

        if (!is_string($value) || !str_starts_with($value, '@')) {
            return;
        }

        $field = trim(substr($value, 1));
        if ($field !== '') {
            $dependencies[] = $field;
        }
    }
}
