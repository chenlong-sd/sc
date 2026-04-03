<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class Actions
{
    public static function make(string $label): Action
    {
        return Action::make($label);
    }

    public static function create(string $label = '新建', string $dialog = 'editor'): Action
    {
        return Action::create($label, $dialog);
    }

    public static function edit(string $label = '编辑', string $dialog = 'editor'): Action
    {
        return Action::edit($label, $dialog);
    }

    public static function delete(string $label = '删除'): Action
    {
        return Action::delete($label);
    }

    public static function refresh(string $label = '刷新'): Action
    {
        return Action::refresh($label);
    }

    public static function submit(string $label = '保存', string $dialog = 'editor'): Action
    {
        return Action::submit($label, $dialog);
    }

    public static function close(string $label = '取消', string $dialog = 'editor'): Action
    {
        return Action::close($label, $dialog);
    }

    public static function custom(string $label, string|JsExpression $handler): Action
    {
        return Action::custom($label, $handler);
    }
}
