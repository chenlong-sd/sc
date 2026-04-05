<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Descriptions implements Renderable, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    private ?string $title = null;
    private int $columns = 3;
    /** @var array<int, array{label:string, value:mixed}> */
    private array $items = [];

    public static function make(): self
    {
        return new self();
    }

    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    public function item(string $label, mixed $value): self
    {
        $this->items[] = ['label' => $label, 'value' => $value];

        return $this;
    }

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
