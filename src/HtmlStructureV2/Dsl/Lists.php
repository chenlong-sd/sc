<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\ListWidget;

final class Lists
{
    /**
     * 创建一个复合列表组件，通常由筛选表单、表格和托管弹窗组成。
     * 若表格列/搜索协议已声明 searchable()/search()，可不显式写 filters()，
     * V2 会自动推导默认筛选表单；若显式写了 filters()，也会自动补齐缺失的筛选项。
     */
    public static function make(string $key): ListWidget
    {
        return ListWidget::make($key);
    }
}
