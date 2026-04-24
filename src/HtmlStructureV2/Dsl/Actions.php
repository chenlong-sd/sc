<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\DialogAction;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class Actions
{
    /**
     * 创建一个通用动作按钮，后续可继续配置 intent、目标和点击行为。
     * 默认按钮类型为 primary。
     *
     * @param string $label 按钮显示文案。
     * @return Action 通用动作实例，可继续链式调用 type()/icon()/onClick() 等方法。
     *
     * 示例：
     * `Actions::make('导出')->icon('Download')->onClick('({ vm }) => vm.handleExport?.()')`
     */
    public static function make(string $label): Action
    {
        return Action::make($label);
    }

    /**
     * 创建“新建”动作。
     * 额外目标（例如 dialog）请继续链式调用 dialog()/bindDialog()。
     *
     * @param string $label 按钮显示文案，默认值为“新建”。
     * @return DialogAction 返回支持 dialog()/bindDialog() 的弹窗动作。
     *
     * 示例：
     * `Actions::create('新增')->dialog('qa-info-dialog')`
     */
    public static function create(string $label = '新建'): DialogAction
    {
        return Action::create($label);
    }

    /**
     * 创建“编辑”动作。
     * 常用于表格行操作；额外目标（例如 dialog）请继续链式调用 dialog()/bindDialog()。
     *
     * @param string $label 按钮显示文案，默认值为“编辑”。
     * @return DialogAction 返回支持 dialog()/bindDialog() 的弹窗动作。
     *
     * 示例：
     * `Actions::edit()->dialog('qa-info-dialog')`
     */
    public static function edit(string $label = '编辑'): DialogAction
    {
        return Action::edit($label);
    }

    /**
     * 创建“删除”动作，默认带批量删除确认提示。
     * 该快捷语义用于表格/列表工具栏里的 selection 批量删除，不用于 rowActions() 单条删除。
     * 如需就近覆盖默认删除配置，可继续链式调用 deleteUrl()/deleteKey()。
     *
     * @param string $label 按钮显示文案，默认值为“删除”。
     * @return Action 删除动作实例。
     *
     * 示例：
     * `Actions::delete()->deleteUrl('/admin/qa-info/delete')->deleteKey('id')`
     */
    public static function delete(string $label = '删除'): Action
    {
        return Action::delete($label);
    }

    /**
     * 创建“刷新”动作，用于刷新当前 table/list 或页面上下文。
     * 如果放在页面头部等没有局部上下文的位置，通常需要再配合 forTable()/forList() 显式指定目标。
     *
     * @param string $label 按钮显示文案，默认值为“刷新”。
     * @return Action 刷新动作实例。
     *
     * 示例：
     * `Actions::refresh()->forTable('qa-info-table')`
     */
    public static function refresh(string $label = '刷新'): Action
    {
        return Action::refresh($label);
    }

    /**
     * 创建一个通用“保存”请求动作。
     * 默认按钮类型为 primary，图标为 Check；
     * 默认会按当前唯一表单或显式 submitForm() 目标执行保存；
     * 若当前动作运行在 dialog footer 中且没有可解析的表单 scope，
     * 会自动回退到当前 dialog 的提交流程，适用于 iframe dialog 等场景。
     *
     * 保存地址、提示文案、submit* 生命周期事件推荐优先配置在 Form 上；
     * 若当前保存场景没有 Form，可回退使用 dialog 的 saveUrl()/createUrl()/updateUrl()。
     *
     * @param string $label 按钮显示文案，默认值为“保存”。
     * @return RequestAction 请求动作实例，默认已配置 icon('Check')->submitForm()。
     *
     * 示例：
     * `Actions::save()->saveUrls('/admin/qa-info/create', '/admin/qa-info/update')`
     */
    public static function save(string $label = '保存'): RequestAction
    {
        return RequestAction::make($label)->icon('Check')->submitForm();
    }

    /**
     * 创建一个独立表单页常用的“重置到初始值”动作。
     * 初始值会优先使用当前表单最近一次初始化/详情加载后的快照；
     * 也就是优先恢复到业务初始化结果，而不是单纯退回 schema 默认值；
     * 若未传表单 key，则仅在当前运行时能唯一定位表单时才会自动解析。
     *
     * @param string $label 按钮显示文案，默认值为“重置”。
     * @param string|null $scope 目标表单 scope；不传时仅在运行时能唯一定位表单时自动解析。
     * @return Action 重置表单动作实例。
     *
     * 示例：
     * `Actions::resetForm('重置', 'qa-info-form')`
     */
    public static function resetForm(string $label = '重置', ?string $scope = null): Action
    {
        return Action::resetForm($label, $scope);
    }

    /**
     * 创建一个请求动作，适合直接发起接口调用并处理成功/失败反馈。
     * 后续可继续链式配置 request()/saveUrls()/payload()/submitForm()/validateForm()/payloadFromForm()/on() /
     * before()/afterSuccess() 等运行时行为。
     * 默认按钮类型为 primary。
     *
     * @param string $label 按钮显示文案。
     * @return RequestAction 请求动作实例。
     *
     * 示例：
     * `Actions::request('同步')->post('/admin/qa-info/sync')->successMessage('同步成功')`
     */
    public static function request(string $label): RequestAction
    {
        return RequestAction::make($label);
    }

    /**
     * 创建一个带导入面板的导入动作。
     * 默认按钮类型为 primary，图标为 Upload；
     * 点击后会打开导入面板，默认支持：
     * - Excel / CSV 文件解析
     * - 模板下载
     * - JSON 导入
     * - AI 测试数据提示词复制
     * - 导入预览和结果展示
     *
     * 最终仍按当前请求动作配置提交到后端。
     * 默认会自动附带：
     * - `"rows"`: 解析后的数据行
     * - `"import_column_info"`: 当前导入列配置
     *
     * 可继续链式配置：
     * - `post()/request()`：提交地址
     * - `importColumns([...])`：导入表头与字段映射
     * - `importColumnsFromForm()` / `importColumnsFromPage()` / `importColumnsFromDialog()`：从现有 V2 表单声明自动推导导入列
     * - `importDialogTitle()` / `importTemplateFileName()`：调整导入面板标题和模板文件名
     * - `enableImportJson()` / `enableImportAiPrompt()` / `importAiPromptText()`：控制 JSON 导入和 AI 提示词能力
     * - `importRowsKey()` / `importColumnInfoKey()`：调整默认请求字段名
     * - `payload()`：完全自定义请求体，JS/context 中可读取 `import.rows` / `import.headers` / `import.fileName`
     * - `successMessage()` / `reloadTable()`：导入成功后的反馈和刷新
     *
     * @param string $label 按钮显示文案，默认值为“导入”。
     * @return RequestAction 导入动作实例。
     *
     * 示例：
     * `Actions::import()->post('/admin/qa-info/import')->importColumns(['name' => '名称'])`
     */
    public static function import(string $label = '导入'): RequestAction
    {
        return RequestAction::make($label)
            ->icon('Upload')
            ->enableImport();
    }

    /**
     * 创建一个独立页面常用的“取消/返回”动作。
     * 默认按钮类型为 default，点击后执行 `Events::returnTo($url)`；
     * 若当前页面是启用 `iframeHost()` 的 V2 iframe 子页，则会优先关闭宿主弹窗；
     * 否则跳转到指定 URL；若 URL 为空，则静默跳过。
     *
     * @param string|JsExpression|null $url 返回目标 URL；可为空，表示仅在可关闭宿主弹窗时生效。
     * @param string $label 按钮显示文案，默认值为“取消”。
     * @return Action 返回/取消动作实例。
     *
     * 示例：
     * `Actions::back('/admin/qa-info/lists', '返回列表')`
     */
    public static function back(string|JsExpression|null $url = null, string $label = '取消'): Action
    {
        return Action::custom($label)
            ->onClick(Events::returnTo($url))
            ->type('default');
    }

    /**
     * 创建“关闭弹窗”动作。
     * 若动作放在 dialog footer 中，会默认关闭当前 dialog；
     * 其它位置如需显式指定目标 dialog，请继续链式调用 dialog()。
     *
     * @param string $label 按钮显示文案，默认值为“取消”。
     * @return Action 关闭弹窗动作实例。
     *
     * 示例：
     * `Actions::close()->dialog('qa-info-dialog')`
     */
    public static function close(string $label = '取消'): Action
    {
        return Action::close($label);
    }

    /**
     * 创建一个自定义动作。
     * 点击逻辑请继续链式调用 onClick() / on('click', ...) 配置；
     * 若传 JS，handler 统一只接收一个 context 对象；
     * 常用可读字段与 Action::on('click', ...) 一致，例如 row / tableKey / listKey /
     * filters / forms / dialogs / selection / vm，以及目标弹窗上下文下的 dialog / dialogKey。
     * 若传 Events::* 返回值，则按结构化事件执行。
     * 默认按钮类型为 primary。
     *
     * @param string $label 按钮显示文案。
     * @return Action 自定义动作实例。
     *
     * 示例：
     * `Actions::custom('查看日志')->onClick('({ row, vm }) => vm.openLogDialog?.(row)')`
     */
    public static function custom(string $label): Action
    {
        return Action::custom($label);
    }
}
