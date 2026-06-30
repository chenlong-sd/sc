<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;

final class DescriptionItem
{
    use HasRenderAttributes;

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
     * 设置当前 item 占用的列数。
     */
    public function span(int $span): self
    {
        return $this->attr(':span', (string) max(1, $span));
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
