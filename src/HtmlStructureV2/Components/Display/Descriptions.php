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
    private bool $equalWidth = true;

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
     * 是否让多列时每列（标签 + 内容）严格等宽，默认 true。
     *
     * true：底层 <table> 切到 table-layout:fixed —— 标签列被 labelWidth 固定，
     * 剩余宽度在各内容列间「平均分」，于是每列宽度一致、不随内容长度变化；
     * 内容过长时自动换行（word-break）而非把某一列撑宽。
     * false：回退到 Element Plus 原生 auto 布局，列宽按内容自适应（多列时通常不等宽）。
     *
     * 注意：等宽依赖本块内所有 item 的 span 一致（默认都是 1）。fixed 布局按
     * 「column 数」把整行划成等宽网格，span=2 的项会占据 2 个网格列 ≈ 2 倍宽，
     * 该行等宽即被打破。想严格等宽就别混用不同 span；确需跨列时可关掉本项。
     */
    public function equalWidth(bool $equalWidth = true): self
    {
        $this->equalWidth = $equalWidth;

        return $this;
    }

    /**
     * 追加一项详情数据。
     *
     * @param string|DescriptionItem $label 标签文案；也可直接传入已配置好的 DescriptionItem 实例，
     *                                       此时若 $value 为数组/闭包，会被当作 $attributes 使用。
     * @param mixed $value 展示值；可为字符串/数字，或 Renderable（如 Displays::image()/videos() 等）。
     * @param array|callable|null $attributes 该 item 的附加配置，支持两种形态：
     *   - 数组：键值对形式。其中 `span` 为特殊键，映射为占用列数（渲染成 `:span`，最小 1）；
     *     其余键按 Element Plus el-descriptions-item 原生属性「原样输出」，故键名需用中划线写法。常用：
     *       · `width`            内容列宽度（px）
     *       · `min-width`        内容列最小宽度（px）
     *       · `align`            内容对齐：left / center / right
     *       · `label-align`      标签对齐：left / center / right
     *       · `class-name`       内容自定义类名
     *       · `label-class-name` 标签自定义类名
     *     动态绑定 / 事件请自行写成 `:prop` / `@event` 形式的键（如 `[':span' => 'cols']`）。
     *   - 闭包：接收 DescriptionItem 实例，可链式调用 span()/labelAlign()/width()/minWidth()/attr() 等自行配置。
     * @return self 当前 descriptions 实例。
     *
     * 示例：
     * - `->item('地址', '北京市朝阳区', ['span' => 2])`                       // 跨 2 列
     * - `->item('金额', '￥100', ['align' => 'right', 'width' => 160])`       // 右对齐 + 定宽
     * - `->item('备注', $remark, fn($item) => $item->span(3)->labelAlign('right'))`
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

    public function isEqualWidth(): bool
    {
        return $this->equalWidth;
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
