<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait LabelWidth
{
    protected ?int $labelWidth = null;

    /**
     * @param int $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth(int $labelWidth): static
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }
}