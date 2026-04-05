<?php

namespace Sc\Util\HtmlStructureV2\Support;

use RuntimeException;
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
        $this->columns = [];
        $this->formNodePathWalker->walk(
            $nodes,
            function (FormNode $node, FormNodePathContext $context) use ($tableName): void {
                if ($node instanceof Field) {
                    $this->columns[] = new FormTableColumnSchema(
                        node: $node,
                        path: $context->fieldPath($node->name()),
                        modelPath: $context->modelPrefix(),
                        label: $context->composeLabel($node->label()),
                    );

                    return;
                }

                if ($node instanceof FormArrayGroup) {
                    throw new RuntimeException(sprintf(
                        'FormTable "%s" does not support nested array node "%s" inside table rows.',
                        $tableName,
                        $node->name()
                    ));
                }

                if ($node instanceof CustomNode) {
                    $this->columns[] = new FormTableColumnSchema(
                        node: $node,
                        path: '',
                        modelPath: $context->modelPrefix(),
                        label: $context->composeLabel('')
                    );
                }
            }
        );

        return $this->columns;
    }
}
