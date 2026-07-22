<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;

final class DescriptionItem
{
    use HasRenderAttributes;

    /**
     * @param string $label 标签文案。
     * @param mixed $value 展示值；可以是字符串/数字，也可以是 Renderable（如 Displays::image/videos 等）——
     *                     若为 Renderable，详情渲染时会走主题渲染器渲染，可正确触发媒体运行时、事件绑定等。
     */
    public function __construct(
        private readonly string $label,
        private readonly mixed $value
    ) {
    }

    /**
     * 直接创建一个 descriptions item 实例。
     */
    public static function make(string $label, mixed $value): self
    {
        return new self($label, $value);
    }

    /**
     * 设置当前 item 占用的列数（动态绑定到 :span）。
     */
    public function span(int $span): self
    {
        return $this->attr(':span', (string) max(1, $span));
    }

    /**
     * 设置标签对齐方式："left" / "center" / "right"。
     * 对应 Element Plus 的 label-align 属性。
     */
    public function labelAlign(string $align): self
    {
        return $this->attr('label-align', $align);
    }

    /**
     * 设置内容列宽度（px，对应 Element Plus 的 width）。
     */
    public function width(int|string $width): self
    {
        return $this->attr('width', (string) $width);
    }

    /**
     * 设置内容列最小宽度（px，对应 Element Plus 的 min-width）。
     */
    public function minWidth(int|string $width): self
    {
        return $this->attr('min-width', (string) $width);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
