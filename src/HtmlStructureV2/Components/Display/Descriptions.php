<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Descriptions implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private ?string $title = null;
    private int $columns = 3;
    /** @var array<int, DescriptionItem> */
    private array $items = [];
    private bool $border = true;
    private ?int $labelWidth = 100;
    private ?string $direction = null;
    private ?string $size = null;
    private ?string $extra = null;

    /**
     * 直接创建一个 descriptions 展示块实例。
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 设置 descriptions 标题。
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置每行展示的列数。
     */
    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    /**
     * 是否显示边框（对应 Element Plus 的 border 属性）。
     * 默认 true 带边框；传 false 切换到无边框简洁样式。
     */
    public function border(bool $border = true): self
    {
        $this->border = $border;

        return $this;
    }

    /**
     * 设置标签列宽度（px，对应 Element Plus 的 label-width）。
     * 默认 100：固定标签列可避免 Element Plus 自适应把长标签的网格宽度
     * 分摊到旁边短标签 item，造成"空内容时 label 列过宽"。
     * 传 null 表示回退到 Element Plus 原生自适应宽度。
     */
    public function labelWidth(?int $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * 设置排列方向："horizontal"（默认）或 "vertical"。
     * 对应 Element Plus 的 direction 属性。
     */
    public function direction(?string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * 设置尺寸："large" / "default" / "small"。
     * 对应 Element Plus 的 size 属性。
     */
    public function size(?string $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * 设置标题右侧操作区文本（对应 Element Plus 的 extra）。
     * 传 null 表示不展示。
     */
    public function extra(?string $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * 追加一项详情数据。
     */
    public function item(string|DescriptionItem $label, mixed $value = null, array|callable|null $attributes = null): self
    {
        if ($label instanceof DescriptionItem) {
            $item = $label;
            if ($attributes === null && (is_array($value) || is_callable($value))) {
                $attributes = $value;
            }
        } else {
            $item = DescriptionItem::make($label, $value);
        }

        if (is_array($attributes)) {
            $this->applyItemAttributes($item, $attributes);
        } elseif (is_callable($attributes)) {
            $attributes($item);
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * 批量追加多项详情数据。
     */
    public function items(array $items): self
    {
        foreach ($items as $label => $value) {
            if (is_string($label)) {
                $this->item($label, $value);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isBorder(): bool
    {
        return $this->border;
    }

    public function getLabelWidth(): ?int
    {
        return $this->labelWidth;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    private function applyItemAttributes(DescriptionItem $item, array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if ($name === 'span') {
                $item->span((int) $value);

                continue;
            }

            $item->attr($name, $value);
        }
    }
}
