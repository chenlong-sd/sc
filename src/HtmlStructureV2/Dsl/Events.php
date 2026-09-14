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
     * 适合绑定到 `on()` 中；传给 `onClick()` 时会被转交给 `on('click', ...)` 执行。
     *
     * 运行时会从当前 handler 的 context 中解析 `query` 里的动态表达式，
     * 具体可用字段以宿主组件 `on()` 注释里的上下文说明为准。
     * 常见场景下可读取：
     * - Action / RequestAction: row / tableKey / listKey / filters / forms / dialogs / selection / vm
     * - Dialog hook: 额外还有 mode / dialogKey / dialogContext / data / dialog
     * - Form / Forms::custom(): 常见有 model / form / scope / fieldName / vm
     * - 页面地址：`page.url` / `page.path` / `page.query`
     * - `query` 传字符串时会自动包装成 JsExpression。
     * 返回的事件对象还可继续链式调用 `target()` / `newTab()` / `features()`。
     *
     * @param string|JsExpression $url 目标 URL。
     * @param array|string|JsExpression $query 查询参数。
     * @return StructuredEvent 打开链接事件。
     *
     * 示例：
     * - `Events::openUrl('/admin/qa-info/form', ['id' => '@row.id'])`
     */
    public static function openUrl(string|JsExpression $url, array|string|JsExpression $query = []): StructuredEvent
    {
        return StructuredEvent::openUrl($url, $query);
    }

    /**
     * 创建一个"在主框架新开一个 tab"事件。
     * 适合从任何支持 `on()` 的位置跳转到另一个页面，又不想把用户甩出主框架的场景。
     * 目标 route 已在 tab 列表中时不会重复开，而是直接切过去；宿主没有 tab 桥时降级为 `window.open(_blank)`。
     * 需要注意本事件只作用于主框架 tab，不改变当前 iframe 自己的地址。
     *
     * - `query` 支持动态表达式，运行时会从当前 handler context 中解析，可用字段同 `openUrl()`；
     *   传字符串时会自动包装成 JsExpression。
     *
     * @param string|JsExpression $route 目标路由。
     * @param string|null $title tab 标题；传 null 时使用默认标题"新页面"。
     * @param string|null $index tab 唯一标识；传 null 时运行时按 route 推导，同一 route 会命中同一个 tab。
     * @param array|string|JsExpression $query 查询参数。
     * @return StructuredEvent 打开主框架 tab 事件。
     *
     * 示例：
     * - `Events::openTab('/admin/qa-info/lists', '问答列表')`
     * - `Events::openTab('/admin/qa-info/form', '编辑问答', null, ['id' => '@row.id'])`
     */
    public static function openTab(
        string|JsExpression $route,
        ?string $title = null,
        ?string $index = null,
        array|string|JsExpression $query = []
    ): StructuredEvent {
        return StructuredEvent::openTab($route, $title, $index, $query);
    }

    /**
     * 创建一个"回退当前页面浏览器历史"事件，等价于在当前 iframe 内执行 `history.back()`。
     * 只回退当前文档自己那一层历史，不关宿主弹窗、不跳 URL、也不动主框架外层。
     * 新建的 iframe（尤其是弹窗子页）历史可能为空，此时运行时会静默跳过并返回 `false`。
     *
     * 注意 `false` 会取消同一事件链上的宿主动作（`isEventCanceled`），
     * 因此不要把它和别的动作混挂在同一事件上——例如挂在 `Dialog` 的 `beforeClose` 上会让弹窗关不掉。
     * 只想"退到上一页"时，单独用一个按钮即可。
     *
     * 需要"优先关掉宿主弹窗，否则跳到指定列表页"时请改用 `Events::returnTo($url)`。
     *
     * @return StructuredEvent 回退历史事件。
     *
     * 示例：
     * - `Events::historyBack()`
     */
    public static function historyBack(): StructuredEvent
    {
        return StructuredEvent::historyBack();
    }

    /**
     * 创建一个打开弹窗事件，目标可传 Dialog 对象或 dialog key。
     *
     * - 默认优先使用事件对象自身的 `dialogKey`；
     * - 打开时若当前 context 中存在 `row` / `tableKey`，也会一并透传给弹窗运行时。
     * - 返回的事件对象还可继续链式调用 `row()` / `table()`，覆盖默认上下文。
     *
     * @param string|Dialog $dialog Dialog 对象或 dialog key。
     * @return StructuredEvent 打开弹窗事件。
     *
     * 示例：
     * - `Events::openDialog('qa-info-dialog')`
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
     * - `Events::closeDialog('qa-info-dialog')`
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
     * - `Events::reloadTable('qa-info-table')`
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
     * - `Events::reloadList('qa-info-list')`
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
     * - `Events::reloadPage()`
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
     * - `Events::closeHostDialog()`
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
     * - `Events::reloadHostTable('qa-info-table')`
     */
    public static function reloadHostTable(string|Table|null $table = null): StructuredEvent
    {
        return StructuredEvent::reloadHostTable($table);
    }

    /**
     * 创建一个在 iframe 宿主页面显示消息提示的事件。
     * 适合 iframe 子页面保存成功后，让父页面弹出成功提示；若当前不在宿主 iframe 弹窗中，运行时会静默跳过。
     *
     * @param string|JsExpression $message 提示文案。
     * @param string $type 提示类型，默认值为 success。
     * @return StructuredEvent 宿主页面消息提示事件。
     *
     * 示例：
     * - `Events::hostMessage('保存成功')`
     */
    public static function hostMessage(string|JsExpression $message, string $type = 'success'): StructuredEvent
    {
        return StructuredEvent::hostMessage($message, $type);
    }

    /**
     * 创建一个“按当前上下文自动识别返回方式”的事件。
     * 适合 iframe 子表单页里的取消返回、保存成功返回，也适合主框架 tab 里的独立表单页。
     * 运行时会按下面顺序分级识别，命中即返回（显式压过隐式）：
     *
     * 1. 当前是 iframe 弹窗子页（`iframeHost()`）→ 关掉宿主弹窗
     * 2. 显式传了 `$url`                       → 在当前 iframe 内跳到该 URL
     * 3. 本帧自己导航过                          → `history.back()`
     * 4. 当前在主框架 tab 里                     → 关掉自己这个 tab，回到上一个 tab
     * 5. 都不适用                               → 静默跳过
     *
     * 第 3 级只在本帧确实自己导航过时才生效——iframe 的 `history` 是顶层联合历史，
     * 无条件 `history.back()` 可能把主框架外壳或别的 tab 导航走，详见 `historyBack()`。
     * 返回的事件对象还可继续链式调用 `hostTable()`。
     *
     * @param string|JsExpression|null $url 回退跳转 URL；传 null 时交给运行时自动识别后续分级。
     * @return StructuredEvent 返回事件。
     *
     * 示例：
     * - `Events::returnTo()`                            // 自动识别
     * - `Events::returnTo('/admin/qa-info/lists')`        // 显式指定目标
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
     * - `values` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 例如可写：
     * - `Events::setFormModel(['status' => 1])`
     * - `Events::setFormModel(['id' => '@row.id'])->form('profile')`
     * - `Events::setFormModel('{ title: row?.title, status: 1 }')->form('profile')`
     *
     * @param array|string|JsExpression $values 要回填到表单的值。
     * @return StructuredEvent 整表赋值事件。
     *
     * 示例：
     * - `Events::setFormModel(['status' => 1])->form('qa-info-form')`
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
     * - `Events::initializeFormModel('@response.data')->form('qa-info-form')`
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
     * - `Events::resetForm('qa-info-form')`
     */
    public static function resetForm(?string $scope = null): StructuredEvent
    {
        return StructuredEvent::resetForm($scope);
    }

    /**
     * 创建一个消息提示事件，type 可传 success / warning / error / info。
     * - `message` 支持动态表达式，会从当前 handler context 中解析。
     * 可用字段同当前宿主组件 `on()` 的上下文，例如 Action / RequestAction 常见有 row /
     * tableKey / listKey / filters / forms / dialogs / selection / vm，Dialog hook 还会额外提供
     * mode / dialogKey / dialogContext / data / dialog。
     *
     * @param string|JsExpression $message 提示文案。
     * @param string $type 提示类型，默认值为 info。
     * @return StructuredEvent 消息提示事件。
     *
     * 示例：
     * - `Events::message('保存成功', 'success')`
     */
    public static function message(string|JsExpression $message, string $type = 'info'): StructuredEvent
    {
        return StructuredEvent::message($message, $type);
    }

    /**
     * 创建一个轻量请求事件，适合在自定义交互里直接发起接口调用。
     *
     * - `query` 支持动态表达式，会从当前 handler context 中解析。
     * 具体可用字段取决于挂载位置，通常就是宿主组件 `on()` 注释里列出的公共上下文；
     * 常见有 row / tableKey / listKey / filters / forms / dialogs / selection / vm，
     * 若运行在弹窗生命周期里，还可读取 mode / dialogKey / dialogContext / data / dialog。
     * - `query` 传字符串时会自动包装成 JsExpression。
     * 返回的事件对象还可继续链式调用 `successMessage()` / `errorMessage()` / `loadingText()`；
     * 请求执行完成后，后续事件可从 context 中读取 `request` / `response` / `payload` / `error`。
     *
     * @param string $url 请求地址。
     * @param string $method 请求方法，默认值为 post。
     * @param array|string|JsExpression $query 请求参数。
     * @return StructuredEvent 轻量请求事件。
     *
     * 示例：
     * - `Events::request('/admin/qa-info/delete', 'post', ['id' => '@row.id'])->successMessage('删除成功')`
     */
    public static function request(
        string $url,
        string $method = 'post',
        array|string|JsExpression $query = []
    ): StructuredEvent {
        return StructuredEvent::request($url, $method, $query);
    }
}
