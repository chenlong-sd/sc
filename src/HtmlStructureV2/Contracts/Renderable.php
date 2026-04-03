<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\RenderContext;

interface Renderable
{
    public function render(RenderContext $context): AbstractHtmlElement;
}
