<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait Placeholder
{
    protected ?string $placeholder = null;

    /**
     * @param string $placeholder
     *
     * @return $this
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }
}