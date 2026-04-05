<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class FormObjectGroup implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;

    /** @var FormNode[] */
    private array $children = [];

    public function __construct(
        private readonly string $name,
        FormNode ...$children
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Form object group name cannot be empty.');
        }

        $this->children = $children;
    }

    public static function make(string $name, FormNode ...$children): self
    {
        return new self($name, ...$children);
    }

    public function addChildren(FormNode ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return FormNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function childNodes(): array
    {
        return $this->children;
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context->nestedObject($this->name());
    }
}
