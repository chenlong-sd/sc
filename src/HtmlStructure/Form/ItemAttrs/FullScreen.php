<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait FullScreen
{
    protected bool $fullScreen = false;

    /**
     * 默认全屏
     *
     * @return $this
     */
    public function defaultFullScreen(): static
    {
        $this->fullScreen = true;

        return $this;
    }
}