<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\RenderContext;

trait RendersWithTheme
{
    public function render(RenderContext $context): AbstractHtmlElement
    {
        return $context->theme()->render($this, $context);
    }
}
