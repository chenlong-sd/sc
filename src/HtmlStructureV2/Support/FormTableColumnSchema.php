<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;

final class FormTableColumnSchema
{
    public function __construct(
        private readonly Field|CustomNode $node,
        private readonly string $path,
        private readonly string $modelPath = '',
        private readonly ?string $label = null,
    ) {
    }

    public function field(): ?Field
    {
        return $this->node instanceof Field ? $this->node : null;
    }

    public function customNode(): ?CustomNode
    {
        return $this->node instanceof CustomNode ? $this->node : null;
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
        if ($this->label !== null) {
            return $this->label;
        }

        return $this->field()?->label() ?? '';
    }
}
