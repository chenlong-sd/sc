<?php
/**
 * datetime: 2023/5/27 23:58
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Table;

/**
 * 表格渲染
 *
 * Interface TableInterface
 *
 * @package Sc\Util\HtmlStructure\Theme\Interfaces
 * @date    2023/5/27
 */
interface TableThemeInterface
{
    public function render(Table $table): AbstractHtmlElement;
}