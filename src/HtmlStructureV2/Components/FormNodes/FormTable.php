<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Contracts\FormNode;

final class FormTable extends FormArrayGroup
{
    private int $minRows = 0;
    private ?int $maxRows = null;
    private string $emptyText = '暂无数据';
    private bool $border = true;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->addButtonText('新增一行');
    }

    /**
     * 追加表格行 schema，并按列方式渲染。
     * 推荐使用这个方法表达“表格里有哪些列”，比通用 `addNodes()` 更符合使用侧认知。
     */
    public function addColumns(FormNode ...$columns): static
    {
        return $this->addNodes(...$columns);
    }

    /**
     * 设置表格至少保留的行数。
     * 数据路径语义与 arrayGroup() 相同，只是 UI 改为表格化编辑。
     */
    public function minRows(int $minRows): static
    {
        $this->minRows = max(0, $minRows);

        return $this;
    }

    /**
     * 设置表格最大行数，传 null 表示不限制。
     */
    public function maxRows(?int $maxRows): static
    {
        $this->maxRows = $maxRows === null ? null : max(0, $maxRows);

        return $this;
    }

    /**
     * 设置表格空数据时的占位文案。
     */
    public function emptyText(string $emptyText): static
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    /**
     * 控制是否显示表格边框。
     */
    public function border(bool $border = true): static
    {
        $this->border = $border;

        return $this;
    }

    public function getMinRows(): int
    {
        return $this->minRows;
    }

    public function getMaxRows(): ?int
    {
        return $this->maxRows;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    public function useBorder(): bool
    {
        return $this->border;
    }

    public function runtimeType(): string
    {
        return 'table';
    }
}
