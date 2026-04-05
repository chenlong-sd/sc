<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

interface RenderableContainer extends Renderable
{
    /**
     * @return Renderable[]
     */
    public function renderChildren(): array;
}
