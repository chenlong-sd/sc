<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasFormTableColumnAttributes
{
    private array $formTableColumnAttributes = [];

    /**
     * 设置当前节点在 Forms::table() 中对应列的固定宽度。
     * 仅在当前节点最终作为表格叶子列渲染时生效。
     */
    public function columnWidth(int|string $width): static
    {
        return $this->columnProp('width', $width);
    }

    /**
     * 设置当前节点在 Forms::table() 中对应列的最小宽度。
     * 仅在当前节点最终作为表格叶子列渲染时生效。
     */
    public function columnMinWidth(int|string $minWidth): static
    {
        return $this->columnProp('min-width', $minWidth);
    }

    /**
     * 设置当前节点在 Forms::table() 中对应列的内容对齐方式。
     * 常用值为 left / center / right。
     */
    public function columnAlign(string $align): static
    {
        return $this->columnProp('align', trim($align));
    }

    /**
     * 设置当前节点在 Forms::table() 中对应列的固定位置。
     * 常用值为 left / right；传 null 可移除该配置。
     */
    public function columnFixed(?string $position = 'left'): static
    {
        if ($position === null) {
            unset($this->formTableColumnAttributes['fixed']);

            return $this;
        }

        $position = trim($position);
        if ($position === '') {
            $position = 'left';
        }

        return $this->columnProp('fixed', $position);
    }

    /**
     * 设置当前节点在 Forms::table() 中对应列的单个底层属性。
     * 属性名会原样输出到 el-table-column 上，例如 width / min-width / align / fixed。
     */
    public function columnProp(string $name, mixed $value): static
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }

        $this->formTableColumnAttributes[$name] = $value;

        return $this;
    }

    /**
     * 批量设置当前节点在 Forms::table() 中对应列的底层属性。
     */
    public function columnProps(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $this->columnProp($name, $value);
        }

        return $this;
    }

    public function getFormTableColumnAttributes(): array
    {
        return $this->formTableColumnAttributes;
    }
}
