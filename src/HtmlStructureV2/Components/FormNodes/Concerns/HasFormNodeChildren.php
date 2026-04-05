<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns;

use Sc\Util\HtmlStructureV2\Contracts\FormNode;

trait HasFormNodeChildren
{
    /** @var FormNode[] */
    private array $children = [];

    protected function setFormNodeChildren(FormNode ...$children): void
    {
        $this->children = $children;
    }

    protected function appendFormNodeChildren(FormNode ...$children): static
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    /**
     * @return FormNode[]
     */
    protected function getFormNodeChildren(): array
    {
        return $this->children;
    }

    public function childNodes(): array
    {
        return $this->children;
    }
}
