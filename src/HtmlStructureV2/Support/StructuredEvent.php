<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;

final class StructuredEvent implements StructuredEventInterface
{
    /** @var array<string, Dialog> */
    private array $dialogs = [];

    /** @var string[] */
    private array $dialogKeys = [];

    /** @var string[] */
    private array $tableKeys = [];

    /** @var string[] */
    private array $listKeys = [];

    private function __construct(
        private readonly string $type,
        private array $payload = [],
    ) {
    }

    /**
     * 创建一个打开链接的结构化事件。
     * `query` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 具体可用字段以宿主组件 `on()` 的上下文为准；
     * 常见有 row / tableKey / listKey / filters / forms / dialogs / selection / vm，
     * 弹窗生命周期里还会额外带上 mode / dialogKey / dialogContext / data / dialog。
     * `query` 传字符串时会自动包装成 JsExpression。
     *
     * @param string|JsExpression $url 目标 URL。
     * @param array|string|JsExpression $query 查询参数。
     * @return self 打开链接事件实例。
     *
     * 示例：
     * `StructuredEvent::openUrl('/admin/qa-info/form', ['id' => '@row.id'])`
     */
    public static function openUrl(string|JsExpression $url, array|string|JsExpression $query = []): self
    {
        return new self('openUrl', [
            'url' => $url,
            'query' => self::normalizeExpressionConfig($query),
            'target' => '_self',
        ]);
    }

    /**
     * 创建一个打开弹窗的结构化事件。
     * 若当前 handler context 中存在 `row` / `tableKey`，运行时会默认一并传给弹窗。
     *
     * @param string|Dialog $dialog Dialog 对象或 dialog key。
     * @return self 打开弹窗事件实例。
     *
     * 示例：
     * `StructuredEvent::openDialog('qa-info-dialog')`
     */
    public static function openDialog(string|Dialog $dialog): self
    {
        $event = new self('openDialog', [
            'dialogKey' => self::dialogKeyOf($dialog),
        ]);

        return $event->registerDialogTarget($dialog);
    }

    /**
     * 创建一个关闭弹窗的结构化事件。
     *
     * @param string|Dialog $dialog Dialog 对象或 dialog key。
     * @return self 关闭弹窗事件实例。
     *
     * 示例：
     * `StructuredEvent::closeDialog('qa-info-dialog')`
     */
    public static function closeDialog(string|Dialog $dialog): self
    {
        $event = new self('closeDialog', [
            'dialogKey' => self::dialogKeyOf($dialog),
        ]);

        return $event->registerDialogTarget($dialog);
    }

    /**
     * 创建一个刷新表格的结构化事件。
     * 若未显式传入表格，运行时会尝试从当前 handler context 读取 `tableKey`。
     *
     * @param string|Table|null $table 目标表格或表格 key；传 null 时运行时自动解析。
     * @return self 刷新表格事件实例。
     *
     * 示例：
     * `StructuredEvent::reloadTable('qa-info-table')`
     */
    public static function reloadTable(string|Table|null $table = null): self
    {
        $event = new self('reloadTable', [
            'tableKey' => self::tableKeyOf($table),
        ]);

        return $event->registerTableTarget($table);
    }

    /**
     * 创建一个刷新列表的结构化事件。
     * 若未显式传入列表，运行时会尝试从当前 handler context 读取 `listKey`。
     *
     * @param string|ListWidget|null $list 目标列表或列表 key；传 null 时运行时自动解析。
     * @return self 刷新列表事件实例。
     *
     * 示例：
     * `StructuredEvent::reloadList('qa-info-list')`
     */
    public static function reloadList(string|ListWidget|null $list = null): self
    {
        $event = new self('reloadList', [
            'listKey' => self::listKeyOf($list),
        ]);

        return $event->registerListTarget($list);
    }

    /**
     * 创建一个整页刷新的结构化事件。
     *
     * @return self 整页刷新事件实例。
     *
     * 示例：
     * `StructuredEvent::reloadPage()`
     */
    public static function reloadPage(): self
    {
        return new self('reloadPage');
    }

    /**
     * 创建一个关闭 iframe 宿主弹窗的结构化事件。
     * 若当前页面不在宿主 iframe 弹窗中，运行时会静默跳过。
     *
     * @return self 关闭宿主弹窗事件实例。
     *
     * 示例：
     * `StructuredEvent::closeHostDialog()`
     */
    public static function closeHostDialog(): self
    {
        return new self('closeHostDialog');
    }

    /**
     * 创建一个刷新 iframe 宿主表格的结构化事件。
     * 若未显式传入表格，运行时会尝试从当前 handler context 读取 `tableKey`。
     * 若当前页面不在宿主 iframe 弹窗中，运行时会静默跳过。
     *
     * @param string|Table|null $table 宿主表格或表格 key；传 null 时运行时自动解析。
     * @return self 刷新宿主表格事件实例。
     *
     * 示例：
     * `StructuredEvent::reloadHostTable('qa-info-table')`
     */
    public static function reloadHostTable(string|Table|null $table = null): self
    {
        $event = new self('reloadHostTable', [
            'tableKey' => self::tableKeyOf($table),
        ]);

        return $event->registerTableTarget($table);
    }

    /**
     * 创建一个“优先关闭宿主弹窗，否则跳转到指定 URL”的结构化事件。
     * 适合 iframe 子表单页里的取消返回、保存成功返回等常见场景。
     * 仅当当前页面由启用 `iframeHost()` 的 V2 iframe 弹窗打开时，才会优先尝试关闭宿主；
     * 其它页面上下文会直接回退到 URL 跳转；若未传 URL，则会静默跳过。
     * 可继续链式调用 `hostTable()`，让关闭前先请求宿主刷新表格。
     *
     * @param string|JsExpression|null $url 回退跳转 URL；传 null 时仅尝试关闭宿主弹窗。
     * @return self 返回事件实例。
     *
     * 示例：
     * `StructuredEvent::returnTo('/admin/qa-info/lists')`
     */
    public static function returnTo(string|JsExpression|null $url = null): self
    {
        return new self('returnTo', [
            'url' => $url,
            'reloadHostTable' => false,
        ]);
    }

    /**
     * 创建一个整表赋值的结构化事件。
     * 适合在表单事件、请求成功后回填等场景下直接替换当前表单 model。
     * 目标表单可继续链式调用 `form('profile')` 显式指定。
     *
     * @param array|string|JsExpression $values 要回填到表单的值。
     * @return self 整表赋值事件实例。
     *
     * 示例：
     * `StructuredEvent::setFormModel(['status' => 1])->form('qa-info-form')`
     */
    public static function setFormModel(array|string|JsExpression $values): self
    {
        return new self('setFormModel', [
            'values' => self::normalizeExpressionConfig($values),
            'formScope' => null,
        ]);
    }

    /**
     * 创建一个按表单 schema 初始化数据的结构化事件。
     * 回填时会自动剔除表单未声明的字段，并按数组组 schema 递归裁剪。
     * 目标表单可继续链式调用 `form('profile')` 显式指定。
     *
     * @param array|string|JsExpression $values 要按 schema 初始化的值。
     * @return self 初始化表单模型事件实例。
     *
     * 示例：
     * `StructuredEvent::initializeFormModel('@response.data')->form('qa-info-form')`
     */
    public static function initializeFormModel(array|string|JsExpression $values): self
    {
        return new self('initializeFormModel', [
            'values' => self::normalizeExpressionConfig($values),
            'formScope' => null,
        ]);
    }

    /**
     * 创建一个把表单恢复到“当前初始值快照”的结构化事件。
     * 常见场景是独立表单页或弹窗表单里的“重置”按钮；
     * 初始值会优先取最近一次初始化、打开弹窗或详情加载成功后的表单快照。
     * 也就是优先恢复到当前业务初始化结果，而不是单纯退回 schema defaults。
     * 目标表单可继续链式调用 `form('profile')` 显式指定。
     *
     * @param string|null $scope 目标表单 scope；传 null 时运行时自动解析。
     * @return self 重置表单事件实例。
     *
     * 示例：
     * `StructuredEvent::resetForm('qa-info-form')`
     */
    public static function resetForm(?string $scope = null): self
    {
        $scope = $scope === null ? null : trim($scope);

        return new self('resetForm', [
            'formScope' => $scope === '' ? null : $scope,
        ]);
    }

    /**
     * 创建一个消息提示的结构化事件。
     * `message` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 可用字段同当前宿主组件 `on()` 的上下文。
     *
     * @param string|JsExpression $message 提示文案。
     * @param string $type 提示类型，默认值为 info。
     * @return self 消息提示事件实例。
     *
     * 示例：
     * `StructuredEvent::message('保存成功', 'success')`
     */
    public static function message(string|JsExpression $message, string $type = 'info'): self
    {
        return new self('message', [
            'message' => $message,
            'messageType' => $type,
        ]);
    }

    /**
     * 创建一个轻量请求的结构化事件。
     * `query` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 具体可用字段同宿主组件 `on()` 的上下文；
     * 常见有 row / tableKey / listKey / filters / forms / dialogs / selection / vm，
     * 若运行在弹窗生命周期里，还可读取 mode / dialogKey / dialogContext / data / dialog。
     * `query` 传字符串时会自动包装成 JsExpression。
     *
     * @param string $url 请求地址。
     * @param string $method 请求方法，默认值为 post。
     * @param array|string|JsExpression $query 请求参数。
     * @return self 轻量请求事件实例。
     *
     * 示例：
     * `StructuredEvent::request('/admin/qa-info/delete', 'post', ['id' => '@row.id'])`
     */
    public static function request(
        string $url,
        string $method = 'post',
        array|string|JsExpression $query = []
    ): self {
        return new self('request', [
            'url' => $url,
            'method' => strtolower($method) ?: 'post',
            'query' => self::normalizeExpressionConfig($query),
            'successMessage' => null,
            'errorMessage' => null,
            'loadingText' => null,
        ]);
    }

    /**
     * 设置打开链接时的窗口目标，例如 `_self` / `_blank`。
     *
     * @param string $target 窗口目标。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::openUrl('/admin/qa-info/form')->target('_blank')`
     */
    public function target(string $target): self
    {
        $this->payload['target'] = $target;

        return $this;
    }

    /**
     * target('_blank') 的语义化别名。
     *
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::openUrl('/admin/qa-info/form')->newTab()`
     */
    public function newTab(): self
    {
        return $this->target('_blank');
    }

    /**
     * 设置 `window.open()` 的 features 字符串，仅在非 `_self` 场景生效。
     *
     * @param string|null $features 浏览器窗口 features 字符串。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::openUrl('/report')->newTab()->features('width=1280,height=800')`
     */
    public function features(?string $features): self
    {
        $this->payload['features'] = $features;

        return $this;
    }

    /**
     * 显式覆盖事件运行时使用的 `row`。
     * 常用于 `openDialog()`，不想依赖当前 handler context 的 `row` 时可手动指定。
     * 若传 JsExpression，运行时同样只接收当前 handler context；
     * 常见可读字段有 row / tableKey / listKey / filters / forms / dialogs / selection / vm。
     *
     * @param string|JsExpression|null $row 行数据或表达式。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::openDialog('qa-info-dialog')->row('@row')`
     */
    public function row(string|JsExpression|null $row): self
    {
        $this->payload['row'] = $row;

        return $this;
    }

    /**
     * 显式覆盖事件运行时使用的 `formScope`。
     * 可用于 `setFormModel()` / `initializeFormModel()` / `resetForm()` 等依赖表单上下文的结构化事件。
     *
     * @param string|null $scope 表单 scope。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::setFormModel(['status' => 1])->form('qa-info-form')`
     */
    public function form(?string $scope): self
    {
        $scope = $scope === null ? null : trim($scope);
        $this->payload['formScope'] = $scope === '' ? null : $scope;

        return $this;
    }

    /**
     * 显式覆盖事件运行时使用的 `tableKey`。
     * 可用于 `openDialog()` / `reloadTable()` 等依赖表格上下文的事件。
     *
     * @param string|Table|null $table 表格对象或表格 key。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::reloadTable()->table('qa-info-table')`
     */
    public function table(string|Table|null $table): self
    {
        $this->payload['tableKey'] = self::tableKeyOf($table);

        return $this->registerTableTarget($table);
    }

    /**
     * 为 `returnTo()` 结构化事件追加“先刷新宿主表格”语义。
     * 若未显式传入表格，运行时会优先读取当前 handler context 的 `tableKey`。
     *
     * @param string|Table|null $table 宿主表格对象或表格 key；传 null 时运行时自动解析。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::returnTo('/admin/qa-info/lists')->hostTable('qa-info-table')`
     */
    public function hostTable(string|Table|null $table = null): self
    {
        $this->payload['reloadHostTable'] = true;
        if ($table !== null) {
            $this->payload['tableKey'] = self::tableKeyOf($table);
            $this->registerTableTarget($table);
        }

        return $this;
    }

    /**
     * 显式覆盖事件运行时使用的 `listKey`。
     * 可用于 `reloadList()` 等依赖列表上下文的事件。
     *
     * @param string|ListWidget|null $list 列表对象或列表 key。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::reloadList()->list('qa-info-list')`
     */
    public function list(string|ListWidget|null $list): self
    {
        $this->payload['listKey'] = self::listKeyOf($list);

        return $this->registerListTarget($list);
    }

    /**
     * 设置 request 结构化事件成功后的提示文案。
     *
     * @param string|null $message 成功提示文案。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::request('/admin/qa-info/sync')->successMessage('同步成功')`
     */
    public function successMessage(?string $message): self
    {
        $this->payload['successMessage'] = $message;

        return $this;
    }

    /**
     * 设置 request 结构化事件失败后的提示文案。
     *
     * @param string|null $message 失败提示文案。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::request('/admin/qa-info/sync')->errorMessage('同步失败')`
     */
    public function errorMessage(?string $message): self
    {
        $this->payload['errorMessage'] = $message;

        return $this;
    }

    /**
     * 设置 request 结构化事件执行中的 loading 文案。
     *
     * @param string|null $message loading 提示文案。
     * @return self 当前事件实例。
     *
     * 示例：
     * `Events::request('/admin/qa-info/sync')->loadingText('同步中...')`
     */
    public function loadingText(?string $message): self
    {
        $this->payload['loadingText'] = $message;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_filter(
            array_merge(['type' => $this->type], $this->payload),
            static fn(mixed $value): bool => $value !== null
        );
    }

    public function referencedDialogs(): array
    {
        return array_values($this->dialogs);
    }

    public function referencedDialogKeys(): array
    {
        return array_values(array_unique($this->dialogKeys));
    }

    public function referencedTableKeys(): array
    {
        return array_values(array_unique($this->tableKeys));
    }

    public function referencedListKeys(): array
    {
        return array_values(array_unique($this->listKeys));
    }

    private static function dialogKeyOf(string|Dialog $dialog): string
    {
        return $dialog instanceof Dialog ? $dialog->key() : trim($dialog);
    }

    private static function tableKeyOf(string|Table|null $table): ?string
    {
        if ($table instanceof Table) {
            return $table->key();
        }

        if (!is_string($table)) {
            return null;
        }

        $table = trim($table);

        return $table === '' ? null : $table;
    }

    private static function listKeyOf(string|ListWidget|null $list): ?string
    {
        if ($list instanceof ListWidget) {
            return $list->key();
        }

        if (!is_string($list)) {
            return null;
        }

        $list = trim($list);

        return $list === '' ? null : $list;
    }

    private function registerDialogTarget(string|Dialog $dialog): self
    {
        $key = self::dialogKeyOf($dialog);
        if ($key !== '') {
            $this->dialogKeys[] = $key;
        }

        if ($dialog instanceof Dialog) {
            $this->dialogs[$dialog->key()] = $dialog;
        }

        return $this;
    }

    private function registerTableTarget(string|Table|null $table): self
    {
        $key = self::tableKeyOf($table);
        if ($key !== null) {
            $this->tableKeys[] = $key;
        }

        return $this;
    }

    private function registerListTarget(string|ListWidget|null $list): self
    {
        $key = self::listKeyOf($list);
        if ($key !== null) {
            $this->listKeys[] = $key;
        }

        return $this;
    }

    private static function normalizeExpressionConfig(array|string|JsExpression $value): array|JsExpression
    {
        return is_string($value) ? JsExpression::ensure($value) : $value;
    }
}
