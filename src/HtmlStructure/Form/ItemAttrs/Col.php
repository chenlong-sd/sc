<?php
/**
 * datetime: 2023/6/4 11:18
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

/**
 * 布局占比
 *
 * Trait Col
 */
trait Col
{
    protected ?int $col = null;

    /**
     * @param int $span
     *
     * @return $this
     */
    public function col(int $span): static
    {
        $this->col = $span;

        return $this;
    }

}