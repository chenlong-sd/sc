<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

/**
 * 表头搜索在表格工具栏中渲染所需的上下文。
 * 由 List 渲染阶段构造（此时能拿到 listKey 与筛选表单 model 变量），
 * 传给表格工具栏渲染，用于拼装关键词输入框与“更多搜索”联动表达式。
 */
final class ListHeaderSearchContext
{
    public function __construct(
        public readonly string $listKey,
        public readonly string $filterModelVar,
        public readonly bool $hasVisibleFilters,
    ) {
    }
}
