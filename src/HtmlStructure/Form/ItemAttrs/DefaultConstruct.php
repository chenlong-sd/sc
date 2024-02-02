<?php
/**
 * datetime: 2023/6/7 0:47
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait DefaultConstruct
{
    public function __construct(protected ?string $name = null, protected ?string $label = null) { }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }
}