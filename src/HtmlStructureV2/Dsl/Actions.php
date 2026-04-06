<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class Actions
{
    /**
     * 创建一个通用动作按钮，后续可继续配置 intent、目标和点击行为。
     */
    public static function make(string $label): Action
    {
        return Action::make($label);
    }

    /**
     * 创建“新建”动作，可直接绑定 Dialog 对象或 dialog key。
     * 传 Dialog 对象时，页面构建阶段会自动收集该弹窗定义。
     */
    public static function create(string|Dialog $labelOrDialog = '新建', string|Dialog|null $dialog = null): Action
    {
        return Action::create($labelOrDialog, $dialog);
    }

    /**
     * 创建“编辑”动作，可直接绑定 Dialog 对象或 dialog key。
     * 常用于表格行操作；若传 Dialog 对象，同样会自动纳入页面弹窗集合。
     */
    public static function edit(string|Dialog $labelOrDialog = '编辑', string|Dialog|null $dialog = null): Action
    {
        return Action::edit($labelOrDialog, $dialog);
    }

    /**
     * 创建“删除”动作，默认带删除确认提示。
     */
    public static function delete(string $label = '删除'): Action
    {
        return Action::delete($label);
    }

    /**
     * 创建“刷新”动作，用于刷新当前 table/list 或页面上下文。
     * 如果放在页面头部等没有局部上下文的位置，通常需要再配合 forTable()/forList() 显式指定目标。
     */
    public static function refresh(string $label = '刷新'): Action
    {
        return Action::refresh($label);
    }

    /**
     * 创建一个请求动作，适合直接发起接口调用并处理成功/失败反馈。
     * 后续可继续链式配置 request()/payload()/on()/before()/afterSuccess() 等运行时行为。
     */
    public static function request(string $label): RequestAction
    {
        return RequestAction::make($label);
    }

    /**
     * 创建“保存并提交弹窗表单”动作，默认目标 dialog key 为 editor。
     */
    public static function submit(string $label = '保存', string $dialog = 'editor'): Action
    {
        return Action::submit($label, $dialog);
    }

    /**
     * 创建“关闭弹窗”动作，默认目标 dialog key 为 editor。
     */
    public static function close(string $label = '取消', string $dialog = 'editor'): Action
    {
        return Action::close($label, $dialog);
    }

    /**
     * 创建一个自定义动作，可绑定 JS 表达式或结构化事件。
     * 若传 JS，handler 统一只接收一个 context 对象；
     * 常用可读字段与 Action::on('click', ...) 一致，例如 row / tableKey / listKey /
     * filters / forms / dialogs / selection / vm，以及目标弹窗上下文下的 dialog / dialogKey。
     * 若传 Events::* 返回值，则按结构化事件执行。
     */
    public static function custom(string $label, string|JsExpression|StructuredEventInterface $handler): Action
    {
        return Action::custom($label, $handler);
    }
}
