<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Table;

final class Tables
{
    /**
     * 创建一个表格组件。
     * key 需要在当前页面内唯一，运行时刷新、删除、事件绑定都会依赖这个 key。
     */
    public static function make(string $key): Table
    {
        return Table::make($key);
    }

    /**
     * 创建一个表格列定义。
     * prop 对应行数据里的字段路径，后续可继续链式配置 display/search/sort 等行为。
     */
    public static function column(string $label, string $prop): Column
    {
        return Column::make($label, $prop);
    }
}
