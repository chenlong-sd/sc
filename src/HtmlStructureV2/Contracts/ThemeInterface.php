<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\RenderContext;

interface ThemeInterface
{
    public function boot(RenderContext $context): void;

    public function render(Renderable $component, RenderContext $context): AbstractHtmlElement;
}
