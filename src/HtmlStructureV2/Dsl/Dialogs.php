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
     *
     * @param string $key 弹窗唯一 key。
     * @param string $title 弹窗标题；支持模板语法。
     * @return Dialog 弹窗实例。
     *
     * 示例：
     * `Dialogs::make('qa-info-dialog', '编辑 {title}')->form(Forms::make('qa-info-form'))`
     */
    public static function make(string $key, string $title): Dialog
    {
        return Dialog::make($key, $title);
    }
}
