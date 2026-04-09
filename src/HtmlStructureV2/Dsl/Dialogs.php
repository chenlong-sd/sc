<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class Dialogs
{
    /**
     * 创建一个弹窗组件，key 用于动作绑定和页面级唯一标识。
     * 该 key 也会用于 Events::openDialog()/closeDialog()，
     * 以及 `Actions::create('新建')->dialog(...)` / `edit('编辑')->dialog(...)` /
     * `submit('保存')->dialog(...)` / `close('取消')->dialog(...)` 这类目标绑定。
     */
    public static function make(string $key, string $title): Dialog
    {
        return Dialog::make($key, $title);
    }
}
