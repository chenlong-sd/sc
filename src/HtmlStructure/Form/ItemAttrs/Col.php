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
    protected ?int $afterCol = null;

    /**
     * @param int      $span     占比，最大 24
     * @param int|null $afterCol 后补占比
     *
     * @return $this
     */
    public function col(int $span, int $afterCol = null): static
    {
        $this->col = $span;
        $this->afterCol = $afterCol;

        return $this;
    }

}