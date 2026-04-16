<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns;

trait HasFormNodeLabelWidth
{
    private ?string $labelWidth = null;

    /**
     * 设置当前容器内字段的 label 宽度，自动下传到容器内的子字段。
     * 字段自身显式设置的 labelWidth 优先级更高；嵌套容器的 labelWidth 会覆盖外层容器。
     * 若不设置，子字段会沿用表单级 labelWidth。
     *
     * @param int|string|null $width label 宽度；数字会自动补上 px 单位，
     *                                字符串按原样处理（如 '120px'、'auto'）；传 null 表示清除。
     * @return static 当前容器实例。
     *
     * 示例：
     * `Forms::section('基础信息')->labelWidth(120)`
     */
    public function labelWidth(int|string|null $width): static
    {
        if (is_int($width)) {
            $width = $width . 'px';
        } elseif (is_string($width) && is_numeric($width) && trim($width) !== '') {
            $width = trim($width) . 'px';
        }

        $this->labelWidth = $width;

        return $this;
    }

    public function getLabelWidth(): ?string
    {
        return $this->labelWidth;
    }
}
