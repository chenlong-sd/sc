<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class Dialogs
{
    /**
     * 创建一个弹窗组件，key 用于动作绑定和页面级唯一标识。
     * 该 key 也会用于 Events::openDialog()/closeDialog()、Actions::create()/edit()/submit()/close() 等目标绑定。
     */
    public static function make(string $key, string $title): Dialog
    {
        return Dialog::make($key, $title);
    }
}
