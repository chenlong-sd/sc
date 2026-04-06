<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class FormObjectGroup implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;

    public function __construct(
        private readonly string $name,
        FormNode ...$children
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Form object group name cannot be empty.');
        }

        $this->setFormNodeChildren(...$children);
    }

    /**
     * 直接创建一个对象作用域节点。
     */
    public static function make(string $name, FormNode ...$children): self
    {
        return new self($name, ...$children);
    }

    /**
     * 继续向当前对象作用域追加子节点。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
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
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context->nestedObject($this->name());
    }
}
