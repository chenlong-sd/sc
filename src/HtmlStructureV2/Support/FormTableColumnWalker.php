<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;

final class FormTableColumnWalker
{
    public function __construct(
        private readonly FormNodePathWalker $formNodePathWalker = new FormNodePathWalker(),
    ) {
    }

    /** @var FormTableColumnSchema[] */
    private array $columns = [];

    /**
     * @param FormNode[] $nodes
     * @return FormTableColumnSchema[]
     */
    public function build(array $nodes, string $tableName): array
    {
        $root = FormTableColumnSchema::group('');
        $this->formNodePathWalker->walk(
            $nodes,
            function (FormNode $node, FormNodePathContext $context) use ($tableName, $root): void {
                if ($node instanceof Field) {
                    $this->appendColumn(
                        $root,
                        $this->splitLabelSegments($context->composeLabel($node->label())),
                        FormTableColumnSchema::fromField(
                            field: $node,
                            path: $context->fieldPath($node->name()),
                            modelPath: $context->modelPrefix(),
                            label: $node->label(),
                        )
                    );

                    return;
                }

                if ($node instanceof CustomNode) {
                    $this->appendColumn(
                        $root,
                        $this->splitLabelSegments($context->composeLabel($node->getColumnLabel() ?? '')),
                        FormTableColumnSchema::fromCustom(
                            customNode: $node,
                            path: '',
                            modelPath: $context->modelPrefix(),
                            label: $node->getColumnLabel() ?? '',
                        )
                    );

                    return;
                }

                if ($node instanceof FormArrayGroup) {
                    $label = $this->resolveArrayGroupColumnLabel($node);
                    $this->appendColumn(
                        $root,
                        $this->splitLabelSegments($context->composeLabel($label)),
                        FormTableColumnSchema::fromArrayGroup(
                            group: $node,
                            path: $context->fieldPath($node->name()),
                            modelPath: $context->modelPrefix(),
                            label: $label,
                        )
                    );
                }
            }
        );

        $this->columns = $this->normalizeColumns($root->children());

        return $this->columns;
    }

    /**
     * @param string[] $segments
     */
    private function appendColumn(FormTableColumnSchema $root, array $segments, FormTableColumnSchema $leaf): void
    {
        $current = $root;
        $leafLabel = array_pop($segments);

        foreach ($segments as $segment) {
            $current = $current->ensureGroupChild($segment);
        }

        $current->appendChild($leaf->withLabel($leafLabel ?? ''));
    }

    /**
     * @param FormTableColumnSchema[] $columns
     * @return FormTableColumnSchema[]
     */
    private function normalizeColumns(array $columns): array
    {
        $normalized = [];

        foreach ($columns as $column) {
            if (!$column->isGroup()) {
                $normalized[] = $column;

                continue;
            }

            $children = $this->normalizeColumns($column->children());
            $label = trim($column->label());

            if ($label === '') {
                $normalized = array_merge($normalized, $children);

                continue;
            }

            if (count($children) <= 1) {
                foreach ($children as $child) {
                    $normalized[] = $this->applyLabelPrefix($label, $child);
                }

                continue;
            }

            $normalized[] = $column->withChildren($children);
        }

        return $normalized;
    }

    private function applyLabelPrefix(string $prefix, FormTableColumnSchema $column): FormTableColumnSchema
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return $column;
        }

        $label = trim($column->label());
        $mergedLabel = $label === '' ? $prefix : sprintf('%s / %s', $prefix, $label);

        if (!$column->isGroup()) {
            return $column->withLabel($mergedLabel);
        }

        return $column->withLabel($mergedLabel);
    }

    /**
     * @return string[]
     */
    private function splitLabelSegments(string $label): array
    {
        $label = trim($label);
        if ($label === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(' / ', $label)),
            static fn(string $segment): bool => $segment !== ''
        ));
    }

    private function resolveArrayGroupColumnLabel(FormArrayGroup $group): string
    {
        $label = trim((string) $group->getTitle());

        return $label !== '' ? $label : $group->name();
    }
}
