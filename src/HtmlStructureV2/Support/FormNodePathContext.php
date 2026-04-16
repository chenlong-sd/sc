<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class FormNodePathContext
{
    /**
     * @param string[] $labelSegments
     */
    public function __construct(
        private readonly string $fieldPrefix = '',
        private readonly string $modelPrefix = '',
        private readonly array $labelSegments = [],
        private readonly bool $readonly = false,
    ) {
    }

    public static function root(): self
    {
        return new self();
    }

    public function fieldPrefix(): string
    {
        return $this->fieldPrefix;
    }

    public function modelPrefix(): string
    {
        return $this->modelPrefix;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function fieldPath(string $name): string
    {
        return FormPath::resolve($this->fieldPrefix, $name);
    }

    public function nestedObject(string $name): self
    {
        $prefix = $this->fieldPath($name);

        return new self(
            fieldPrefix: $prefix,
            modelPrefix: $prefix,
            labelSegments: $this->labelSegments,
            readonly: $this->readonly,
        );
    }

    public function withLabelSegment(?string $label): self
    {
        $label = trim((string) $label);
        if ($label === '') {
            return $this;
        }

        $segments = $this->labelSegments;
        $segments[] = $label;

        return new self(
            fieldPrefix: $this->fieldPrefix,
            modelPrefix: $this->modelPrefix,
            labelSegments: $segments,
            readonly: $this->readonly,
        );
    }

    public function mergeReadonly(bool $readonly = true): self
    {
        return new self(
            fieldPrefix: $this->fieldPrefix,
            modelPrefix: $this->modelPrefix,
            labelSegments: $this->labelSegments,
            readonly: $this->readonly || $readonly,
        );
    }

    public function composeLabel(string $label): string
    {
        $segments = array_values(array_filter(
            [...$this->labelSegments, $label],
            static fn(string $segment) => trim($segment) !== ''
        ));

        return $segments === []
            ? $label
            : implode(' / ', $segments);
    }
}
