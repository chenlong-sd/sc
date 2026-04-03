<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Contracts\Fields\SearchableFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Form implements Renderable
{
    use RendersWithTheme;

    private array $fields = [];
    private bool $inline = false;
    private string $labelWidth = '100px';
    private string $submitLabel = '查询';
    private string $resetLabel = '重置';

    public function __construct(
        private readonly string $key
    ) {
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function addFields(Field ...$fields): self
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    public function labelWidth(string $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    public function submitLabel(string $submitLabel): self
    {
        $this->submitLabel = $submitLabel;

        return $this;
    }

    public function resetLabel(string $resetLabel): self
    {
        $this->resetLabel = $resetLabel;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getLabelWidth(): string
    {
        return $this->labelWidth;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getResetLabel(): string
    {
        return $this->resetLabel;
    }

    public function defaults(): array
    {
        $defaults = [];
        foreach ($this->fields as $field) {
            $defaults[$field->name()] = $field->getDefault();
        }

        return $defaults;
    }

    public function rules(): array
    {
        $rules = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof ValidatableFieldInterface || !$field->hasRules()) {
                continue;
            }

            $rules[$field->name()] = $field->getRules();
        }

        return $rules;
    }

    public function remoteOptions(): array
    {
        $schema = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof OptionField || !$field->hasRemoteOptions()) {
                continue;
            }

            $schema[$field->name()] = array_merge(
                $field->getRemoteOptions() ?? [],
                ['initialOptions' => $field->getOptions()]
            );
        }

        return $schema;
    }

    public function uploads(): array
    {
        $schema = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof UploadField || !$field->hasUpload()) {
                continue;
            }

            $schema[$field->name()] = $field->getUpload();
        }

        return $schema;
    }

    public function selectOptions(): array
    {
        $schema = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof OptionField) {
                continue;
            }

            $schema[$field->name()] = $field->getOptions();
        }

        return $schema;
    }

    public function linkages(): array
    {
        $schema = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof OptionField || !$field->hasLinkageUpdates()) {
                continue;
            }

            $schema[$field->name()] = $field->getLinkageConfig() ?? [];
        }

        return $schema;
    }

    public function searchSchema(): array
    {
        $schema = [];
        foreach ($this->fields as $field) {
            if (!$field instanceof SearchableFieldInterface) {
                continue;
            }

            $schema[$field->name()] = [
                'type' => $field->getSearchType(),
                'field' => $field->getSearchField(),
            ];
        }

        return $schema;
    }
}
