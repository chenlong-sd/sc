<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\ListWidget;

final class Lists
{
    /**
     * 创建一个复合列表组件，通常由筛选表单、表格和托管弹窗组成。
     */
    public static function make(string $key): ListWidget
    {
        return ListWidget::make($key);
    }
}
