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
    /** @var array<int, array{label:string, value:mixed}> */
    private array $items = [];

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
     * 追加一项详情数据。
     */
    public function item(string $label, mixed $value): self
    {
        $this->items[] = ['label' => $label, 'value' => $value];

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
}
