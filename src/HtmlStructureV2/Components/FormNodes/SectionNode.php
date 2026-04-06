<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class SectionNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;

    /** @var Action[] */
    private array $headerActions = [];
    private ?string $description = null;
    private bool $plain = false;

    public function __construct(
        private readonly string $title
    ) {
    }

    /**
     * 直接创建一个表单分组节点。
     */
    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * 继续向当前分组追加子节点。
     */
    public function addChildren(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 设置分组标题下方的说明文字。
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * 在分组头部右侧追加操作按钮。
     */
    public function headerActions(Action ...$actions): self
    {
        $this->headerActions = array_merge($this->headerActions, $actions);

        return $this;
    }

    /**
     * 取消默认卡片外壳，仅保留分组头和内部布局。
     */
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
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context->withLabelSegment($this->title());
    }

    public function descriptionText(): ?string
    {
        return $this->description;
    }

    /**
     * @return Action[]
     */
    public function getHeaderActions(): array
    {
        return $this->headerActions;
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }
}
