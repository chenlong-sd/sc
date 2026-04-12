<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;

final class FormTableColumnSchema
{
    /** @var self[] */
    private array $children = [];

    private function __construct(
        private readonly Field|CustomNode|FormArrayGroup|null $node = null,
        private readonly string $path = '',
        private readonly string $modelPath = '',
        private string $label = '',
        private readonly array $columnAttributes = [],
        array $children = [],
    ) {
        $this->children = array_values($children);
    }

    public static function fromField(
        Field $field,
        string $path,
        string $modelPath = '',
        ?string $label = null
    ): self {
        return new self(
            node: $field,
            path: $path,
            modelPath: $modelPath,
            label: $label ?? $field->label(),
            columnAttributes: $field->getFormTableColumnAttributes(),
        );
    }

    public static function fromCustom(
        CustomNode $customNode,
        string $path = '',
        string $modelPath = '',
        ?string $label = null
    ): self {
        return new self(
            node: $customNode,
            path: $path,
            modelPath: $modelPath,
            label: $label ?? '',
            columnAttributes: $customNode->getFormTableColumnAttributes(),
        );
    }

    public static function fromArrayGroup(
        FormArrayGroup $group,
        string $path,
        string $modelPath = '',
        ?string $label = null
    ): self {
        return new self(
            node: $group,
            path: $path,
            modelPath: $modelPath,
            label: $label ?? $group->name(),
            columnAttributes: $group->getFormTableColumnAttributes(),
        );
    }

    /**
     * @param self[] $children
     */
    public static function group(string $label, array $children = []): self
    {
        return new self(
            node: null,
            label: $label,
            columnAttributes: [],
            children: $children,
        );
    }

    public function isGroup(): bool
    {
        return $this->node === null;
    }

    /**
     * @return self[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function appendChild(self $child): void
    {
        $this->children[] = $child;
    }

    public function ensureGroupChild(string $label): self
    {
        foreach ($this->children as $child) {
            if ($child->isGroup() && $child->label() === $label) {
                return $child;
            }
        }

        $group = self::group($label);
        $this->children[] = $group;

        return $group;
    }

    public function withLabel(string $label): self
    {
        return new self(
            node: $this->node,
            path: $this->path,
            modelPath: $this->modelPath,
            label: $label,
            columnAttributes: $this->columnAttributes,
            children: $this->children,
        );
    }

    /**
     * @param self[] $children
     */
    public function withChildren(array $children): self
    {
        return new self(
            node: $this->node,
            path: $this->path,
            modelPath: $this->modelPath,
            label: $this->label,
            columnAttributes: $this->columnAttributes,
            children: $children,
        );
    }

    public function field(): ?Field
    {
        return $this->node instanceof Field ? $this->node : null;
    }

    public function customNode(): ?CustomNode
    {
        return $this->node instanceof CustomNode ? $this->node : null;
    }

    public function arrayGroup(): ?FormArrayGroup
    {
        return $this->node instanceof FormArrayGroup ? $this->node : null;
    }

    public function isField(): bool
    {
        return $this->node instanceof Field;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function modelPath(): string
    {
        return $this->modelPath;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function columnAttributes(): array
    {
        return $this->columnAttributes;
    }
}
