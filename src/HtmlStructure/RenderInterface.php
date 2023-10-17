<?php
/**
 * datetime: 2023/5/28 0:38
 **/

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * 渲染接口
 *
 * Interface RenderInterface
 *
 * @package Sc\Util\HtmlStructure
 * @date    2023/5/28
 */
interface RenderInterface
{
    public function render(#[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = null): AbstractHtmlElement;
}