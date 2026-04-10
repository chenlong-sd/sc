<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Table;

final class Tables
{
    /**
     * 创建一个表格组件。
     * key 需要在当前页面内唯一，运行时刷新、删除、事件绑定都会依赖这个 key。
     *
     * @param string $key 表格唯一 key。
     * @return Table 表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dataUrl('/admin/qa-info/list')`
     */
    public static function make(string $key): Table
    {
        return Table::make($key);
    }

    /**
     * 创建一个表格列定义。
     * prop 对应行数据里的字段路径，后续可继续链式配置 display/search/sort 等行为。
     *
     * @param string $label 列标题。
     * @param string $prop 行数据字段路径。
     * @return Column 列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->searchable('LIKE')`
     */
    public static function column(string $label, string $prop): Column
    {
        return Column::make($label, $prop);
    }
}
