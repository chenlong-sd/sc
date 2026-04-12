<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\StructuredEvent;

final class Events
{
    /**
     * 创建一个打开链接事件，可带查询参数。
     * 适合绑定到 `on()` / `onClick()` 中。
     *
     * 运行时会从当前 handler 的 context 中解析 `query` 里的动态表达式，
     * 具体可用字段以宿主组件 `on()` 注释里的上下文说明为准。
     * 常见场景下可读取：
     * - Action / RequestAction: row / tableKey / listKey / filters / forms / dialogs / selection / vm
     * - Dialog hook: 额外还有 mode / dialogKey / dialogContext / data / dialog
     * - Form / Forms::custom(): 常见有 model / form / scope / fieldName / vm
     * - 页面地址：`page.url` / `page.path` / `page.query`
     * `query` 传字符串时会自动包装成 JsExpression。
     * 返回的事件对象还可继续链式调用 `target()` / `newTab()` / `features()`。
     *
     * @param string|JsExpression $url 目标 URL。
     * @param array|string|JsExpression $query 查询参数。
     * @return StructuredEvent 打开链接事件。
     *
     * 示例：
     * `Events::openUrl('/admin/qa-info/form', ['id' => '@row.id'])`
     */
    public static function openUrl(string|JsExpression $url, array|string|JsExpression $query = []): StructuredEvent
    {
        return StructuredEvent::openUrl($url, $query);
    }

    /**
     * 创建一个打开弹窗事件，目标可传 Dialog 对象或 dialog key。
     *
     * 默认优先使用事件对象自身的 `dialogKey`；
     * 打开时若当前 context 中存在 `row` / `tableKey`，也会一并透传给弹窗运行时。
     * 返回的事件对象还可继续链式调用 `row()` / `table()`，覆盖默认上下文。
     *
     * @param string|Dialog $dialog Dialog 对象或 dialog key。
     * @return StructuredEvent 打开弹窗事件。
     *
     * 示例：
     * `Events::openDialog('qa-info-dialog')`
     */
    public static function openDialog(string|Dialog $dialog): StructuredEvent
    {
        return StructuredEvent::openDialog($dialog);
    }

    /**
     * 创建一个关闭弹窗事件，目标可传 Dialog 对象或 dialog key。
     *
     * 适合在按钮点击、请求成功后的附加事件里关闭指定弹窗。
     * 运行时需要当前页面存在 dialog runtime 支持。
     *
     * @param string|Dialog $dialog Dialog 对象或 dialog key。
     * @return StructuredEvent 关闭弹窗事件。
     *
     * 示例：
     * `Events::closeDialog('qa-info-dialog')`
     */
    public static function closeDialog(string|Dialog $dialog): StructuredEvent
    {
        return StructuredEvent::closeDialog($dialog);
    }

    /**
     * 创建一个刷新表格事件，留空时使用当前上下文表格。
     *
     * 若未显式传入表格，则运行时会回退读取当前 handler context 中的 `tableKey`；
     * 因此通常适合放在表格动作、行动作、弹窗事件等本身带表格上下文的位置。
     *
     * @param string|Table|null $table 目标表格或表格 key；传 null 时使用当前上下文。
     * @return StructuredEvent 刷新表格事件。
     *
     * 示例：
     * `Events::reloadTable('qa-info-table')`
     */
    public static function reloadTable(string|Table|null $table = null): StructuredEvent
    {
        return StructuredEvent::reloadTable($table);
    }

    /**
     * 创建一个刷新列表事件，留空时使用当前上下文列表。
     *
     * 若未显式传入列表，则运行时会回退读取当前 handler context 中的 `listKey`；
     * 因此通常适合放在列表级事件或显式通过 `list()` 指定目标的场景。
     *
     * @param string|ListWidget|null $list 目标列表或列表 key；传 null 时使用当前上下文。
     * @return StructuredEvent 刷新列表事件。
     *
     * 示例：
     * `Events::reloadList('qa-info-list')`
     */
    public static function reloadList(string|ListWidget|null $list = null): StructuredEvent
    {
        return StructuredEvent::reloadList($list);
    }

    /**
     * 创建一个整页刷新事件。
     * 优先调用当前 context 中的 `reloadPage()`，否则直接执行浏览器 `window.location.reload()`。
     *
     * @return StructuredEvent 整页刷新事件。
     *
     * 示例：
     * `Events::reloadPage()`
     */
    public static function reloadPage(): StructuredEvent
    {
        return StructuredEvent::reloadPage();
    }

    /**
     * 创建一个关闭 iframe 宿主弹窗的事件。
     * 适合 iframe 子页面里的取消操作；若当前不在宿主 iframe 弹窗中，运行时会静默跳过。
     *
     * @return StructuredEvent 关闭宿主弹窗事件。
     *
     * 示例：
     * `Events::closeHostDialog()`
     */
    public static function closeHostDialog(): StructuredEvent
    {
        return StructuredEvent::closeHostDialog();
    }

    /**
     * 创建一个刷新 iframe 宿主表格的事件，留空时使用当前上下文表格。
     * 若当前不在宿主 iframe 弹窗中，运行时会静默跳过。
     *
     * @param string|Table|null $table 宿主表格或表格 key；传 null 时使用当前上下文。
     * @return StructuredEvent 刷新宿主表格事件。
     *
     * 示例：
     * `Events::reloadHostTable('qa-info-table')`
     */
    public static function reloadHostTable(string|Table|null $table = null): StructuredEvent
    {
        return StructuredEvent::reloadHostTable($table);
    }

    /**
     * 创建一个“优先关闭宿主弹窗，否则跳转到 URL”的事件。
     * 适合 iframe 子表单页里的取消返回、保存成功返回。
     * 仅当当前页面由启用 `iframeHost()` 的 V2 iframe 弹窗打开时，才会优先尝试关闭宿主；
     * 其它页面上下文会直接回退到 URL 跳转；若未传 URL，则会静默跳过。
     * 返回的事件对象还可继续链式调用 `hostTable()`。
     *
     * @param string|JsExpression|null $url 回退跳转 URL；传 null 时仅尝试关闭宿主弹窗。
     * @return StructuredEvent 返回事件。
     *
     * 示例：
     * `Events::returnTo('/admin/qa-info/lists')`
     */
    public static function returnTo(string|JsExpression|null $url = null): StructuredEvent
    {
        return StructuredEvent::returnTo($url);
    }

    /**
     * 创建一个整表赋值事件。
     * 适合在 `on()` / `afterSuccess()` 这类结构化事件里，直接回填当前表单或指定表单。
     * 目标表单可继续链式调用 `->form('profile')` 显式指定；
     * 不指定时，运行时会优先尝试使用当前事件上下文里的表单 scope。
     *
     * `values` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 例如可写：
     * - `Events::setFormModel(['status' => 1])`
     * - `Events::setFormModel(['id' => '@row.id'])->form('profile')`
     * - `Events::setFormModel('{ title: row?.title, status: 1 }')->form('profile')`
     *
     * @param array|string|JsExpression $values 要回填到表单的值。
     * @return StructuredEvent 整表赋值事件。
     *
     * 示例：
     * `Events::setFormModel(['status' => 1])->form('qa-info-form')`
     */
    public static function setFormModel(array|string|JsExpression $values): StructuredEvent
    {
        return StructuredEvent::setFormModel($values);
    }

    /**
     * 创建一个按表单 schema 初始化数据的事件。
     * 与 `setFormModel()` 不同，这里会在回填时剔除表单未声明的字段；
     * 对数组组会按行 schema 递归裁剪。
     * 目标表单可继续链式调用 `->form('profile')` 显式指定。
     *
     * @param array|string|JsExpression $values 要按 schema 初始化的值。
     * @return StructuredEvent 初始化表单模型事件。
     *
     * 示例：
     * `Events::initializeFormModel('@response.data')->form('qa-info-form')`
     */
    public static function initializeFormModel(array|string|JsExpression $values): StructuredEvent
    {
        return StructuredEvent::initializeFormModel($values);
    }

    /**
     * 创建一个把表单恢复到“当前初始值快照”的事件。
     * 初始值会优先取最近一次初始化、详情加载成功或弹窗打开时形成的快照；
     * 也就是优先恢复到当前业务初始化结果，而不是单纯退回 schema defaults；
     * 目标表单可继续链式调用 `->form('profile')` 显式指定。
     *
     * @param string|null $scope 目标表单 scope；传 null 时运行时自动解析。
     * @return StructuredEvent 重置表单事件。
     *
     * 示例：
     * `Events::resetForm('qa-info-form')`
     */
    public static function resetForm(?string $scope = null): StructuredEvent
    {
        return StructuredEvent::resetForm($scope);
    }

    /**
     * 创建一个消息提示事件，type 可传 success / warning / error / info。
     * `message` 支持动态表达式，会从当前 handler context 中解析。
     * 可用字段同当前宿主组件 `on()` 的上下文，例如 Action / RequestAction 常见有 row /
     * tableKey / listKey / filters / forms / dialogs / selection / vm，Dialog hook 还会额外提供
     * mode / dialogKey / dialogContext / data / dialog。
     *
     * @param string|JsExpression $message 提示文案。
     * @param string $type 提示类型，默认值为 info。
     * @return StructuredEvent 消息提示事件。
     *
     * 示例：
     * `Events::message('保存成功', 'success')`
     */
    public static function message(string|JsExpression $message, string $type = 'info'): StructuredEvent
    {
        return StructuredEvent::message($message, $type);
    }

    /**
     * 创建一个轻量请求事件，适合在自定义交互里直接发起接口调用。
     *
     * `query` 支持动态表达式，会从当前 handler context 中解析。
     * 具体可用字段取决于挂载位置，通常就是宿主组件 `on()` 注释里列出的公共上下文；
     * 常见有 row / tableKey / listKey / filters / forms / dialogs / selection / vm，
     * 若运行在弹窗生命周期里，还可读取 mode / dialogKey / dialogContext / data / dialog。
     * `query` 传字符串时会自动包装成 JsExpression。
     * 返回的事件对象还可继续链式调用 `successMessage()` / `errorMessage()` / `loadingText()`；
     * 请求执行完成后，后续事件可从 context 中读取 `request` / `response` / `payload` / `error`。
     *
     * @param string $url 请求地址。
     * @param string $method 请求方法，默认值为 post。
     * @param array|string|JsExpression $query 请求参数。
     * @return StructuredEvent 轻量请求事件。
     *
     * 示例：
     * `Events::request('/admin/qa-info/delete', 'post', ['id' => '@row.id'])->successMessage('删除成功')`
     */
    public static function request(
        string $url,
        string $method = 'post',
        array|string|JsExpression $query = []
    ): StructuredEvent {
        return StructuredEvent::request($url, $method, $query);
    }
}
