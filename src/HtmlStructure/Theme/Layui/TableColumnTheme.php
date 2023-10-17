<?php
/**
 * datetime: 2023/5/27 23:39
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableColumnThemeInterface;

/**
 * Class TableColumn
 *
 * @package Sc\Util\HtmlStructure\Theme\Layui
 * @date    2023/5/27
 */
class TableColumnTheme implements TableColumnThemeInterface
{
    /**
     * @param Column $column
     *
     * @return AbstractHtmlElement
     * @date 2023/5/27
     */
    public function render(Column $column): AbstractHtmlElement
    {
        return new FictitiousLabel();
    }
}