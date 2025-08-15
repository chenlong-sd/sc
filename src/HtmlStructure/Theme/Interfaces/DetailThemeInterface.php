<?php

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Detail;

/**
 * 详情渲染接口
 *
 * Interface DetailThemeInterface
 */
interface DetailThemeInterface
{
    public function render(Detail $detail): AbstractHtmlElement;
}