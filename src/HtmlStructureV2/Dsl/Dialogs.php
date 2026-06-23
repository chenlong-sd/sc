<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class Dialogs
{
    /**
     * 创建一个弹窗组件，key 用于动作绑定和页面级唯一标识。
     * 当弹窗对象直接传给 action/picker/page 时，可只传标题并自动生成内部 key。
     * 自动 key 不承诺跨请求固定；需要字符串引用时请显式传 key。
     * 显式 key 会用于 Events::openDialog()/closeDialog()，
     * 以及 `Actions::create('新建')->dialog(...)` / `edit('编辑')->dialog(...)` /
     * - `submit('保存')->dialog(...)` / `close('取消')->dialog(...)` 这类目标绑定。
     * 若 submit/close 已经写在当前 dialog 的 footer(...) 中，则可省略 `->dialog(...)`。
     *
     * @param string $keyOrTitle 弹窗唯一 key，或省略 key 时的弹窗标题。
     * @param string|null $title 弹窗标题；支持模板语法。传 null 时自动生成内部 key。
     * @return Dialog 弹窗实例。
     *
     * 示例：
     * - `Dialogs::make('qa-info-dialog', '编辑 {title}')->form(Forms::make('qa-info-form'))`
     * - `Dialogs::make('编辑 {title}')->form(Forms::make())`
     */
    public static function make(string $keyOrTitle, ?string $title = null): Dialog
    {
        return Dialog::make($keyOrTitle, $title);
    }
}
