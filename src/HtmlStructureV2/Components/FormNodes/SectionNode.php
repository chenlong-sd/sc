<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasReadonly;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class SectionNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasRenderAttributes;
    use HasSpan;
    use HasFormNodeChildren;
    use HasReadonly;

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
     *
     * @param string $title 分组标题。
     * @return self 分组节点实例。
     *
     * 示例：
     * `SectionNode::make('基础信息')`
     */
    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * 追加当前分组内容。
     * 推荐使用这个方法表达“这个 section 里放什么内容”。
     *
     * @param FormNode ...$children 要追加的内容节点。
     * @return self 当前分组节点。
     *
     * 示例：
     * `Forms::section('基础信息')->addContent(Fields::text('title', '标题'))`
     */
    public function addContent(FormNode ...$children): self
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 设置分组标题下方的说明文字。
     *
     * @param string|null $description 分组说明文案。
     * @return self 当前分组节点。
     *
     * 示例：
     * `Forms::section('基础信息')->description('请先填写标题和分类')`
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * 在分组头部右侧追加操作按钮。
     *
     * @param Action ...$actions 头部动作按钮。
     * @return self 当前分组节点。
     *
     * 示例：
     * `Forms::section('附件')->headerActions(Actions::make('新增附件'))`
     */
    public function headerActions(Action ...$actions): self
    {
        $this->headerActions = array_merge($this->headerActions, $actions);

        return $this;
    }

    /**
     * 取消默认卡片外壳，仅保留分组头和内部布局。
     *
     * @param bool $plain 是否使用纯布局模式，默认值为 true。
     * @return self 当前分组节点。
     *
     * 示例：
     * `Forms::section('基础信息')->plain()`
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
        return $context
            ->withLabelSegment($this->title())
            ->mergeReadonly($this->isReadonly());
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
        return array_values(array_filter(
            $this->headerActions,
            static fn (Action $action): bool => $action->isAvailable()
        ));
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }
}
