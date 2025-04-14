<?php
/**
 * datetime: 2023/5/27 23:36
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Table\Column;

/**
 * 表格列渲染
 *
 * Interface TableColumnInterface
 *
 * @package Sc\Util\HtmlStructure\Theme\Interfaces
 * @date    2023/5/27
 */
interface TableColumnThemeInterface
{
    public function render(Column $column): AbstractHtmlElement;
}