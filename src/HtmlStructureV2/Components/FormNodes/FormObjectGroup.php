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
        private readonly string $name
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Form object group name cannot be empty.');
        }
    }

    /**
     * 直接创建一个对象作用域节点。
     *
     * @param string $name 对象字段名。
     * @return self 对象作用域节点实例。
     *
     * 示例：
     * `FormObjectGroup::make('profile')`
     */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * 追加当前对象作用域下的 schema。
     * 推荐使用这个方法表达“这个对象下有哪些字段/结构”。
     *
     * @param FormNode ...$children 要追加的 schema 节点。
     * @return self 当前对象作用域节点。
     *
     * 示例：
     * `Forms::object('profile')->addSchema(Fields::text('name', '姓名'))`
     */
    public function addSchema(FormNode ...$children): self
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
