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
}
