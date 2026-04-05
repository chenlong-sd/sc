<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class SectionNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;

    /** @var FormNode[] */
    private array $children = [];
    private ?string $description = null;
    private bool $plain = false;

    public function __construct(
        private readonly string $title
    ) {
    }

    public static function make(string $title): self
    {
        return new self($title);
    }

    public function addChildren(FormNode ...$children): self
    {
        $this->children = array_merge($this->children, $children);

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function plain(bool $plain = true): self
    {
        $this->plain = $plain;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
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
        return $context->withLabelSegment($this->title());
    }

    public function descriptionText(): ?string
    {
        return $this->description;
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }
}
