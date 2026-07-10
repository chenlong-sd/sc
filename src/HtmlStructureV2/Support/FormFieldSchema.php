<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;

final class FormFieldSchema
{
    public function __construct(
        private readonly Field $field,
        private readonly string $path,
    ) {
    }

    public function field(): Field
    {
        return $this->field;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function segments(): array
    {
        return FormPath::segments($this->path);
    }

    public function parentPath(): ?string
    {
        return FormPath::parent($this->path);
    }

    public function runtimeMeta(): array
    {
        return [
            'name' => $this->field->name(),
            'path' => $this->path(),
            'label' => $this->field->label(),
            'type' => $this->field->type()->value,
            'visible' => $this->field->isVisible(),
            'disabled' => $this->field->isDisabled(),
            'readonly' => $this->field->isReadonly(),
            'props' => $this->field->getProps(),
        ];
    }
}
