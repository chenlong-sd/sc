<?php

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait Slots
{
    /**
     * @var array{string, string}
     */
    protected array $slots = [];

    public function prefix(string|\Stringable $slot): static
    {
        $this->slot("prefix", $slot);
        return $this;
    }

    public function suffix(string|\Stringable $slot): static
    {
        $this->slot("suffix", $slot);
        return $this;
    }

    public function prepend(string|\Stringable $slot): static
    {
        $this->slot("prepend", $slot);
        return $this;
    }

    public function append(string|\Stringable $slot): static
    {
        $this->slot("append", $slot);
        return $this;
    }

    public function slot(string $slotName, string|\Stringable $slot): static
    {
        $this->slots[$slotName] = $slot;
        return $this;
    }

    public function getSlots(): array
    {
        return $this->slots;
    }
}