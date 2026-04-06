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
     */
    public static function reloadPage(): self
    {
        return new self('reloadPage');
    }

    /**
     * 创建一个消息提示的结构化事件。
     * `message` 支持动态表达式，运行时会从当前 handler context 中解析。
     * 可用字段同当前宿主组件 `on()` 的上下文。
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
     */
    public function target(string $target): self
    {
        $this->payload['target'] = $target;

        return $this;
    }

    /**
     * target('_blank') 的语义化别名。
     */
    public function newTab(): self
    {
        return $this->target('_blank');
    }

    /**
     * 设置 `window.open()` 的 features 字符串，仅在非 `_self` 场景生效。
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
     */
    public function row(string|JsExpression|null $row): self
    {
        $this->payload['row'] = $row;

        return $this;
    }

    /**
     * 显式覆盖事件运行时使用的 `tableKey`。
     * 可用于 `openDialog()` / `reloadTable()` 等依赖表格上下文的事件。
     */
    public function table(string|Table|null $table): self
    {
        $this->payload['tableKey'] = self::tableKeyOf($table);

        return $this->registerTableTarget($table);
    }

    /**
     * 显式覆盖事件运行时使用的 `listKey`。
     * 可用于 `reloadList()` 等依赖列表上下文的事件。
     */
    public function list(string|ListWidget|null $list): self
    {
        $this->payload['listKey'] = self::listKeyOf($list);

        return $this->registerListTarget($list);
    }

    /**
     * 设置 request 结构化事件成功后的提示文案。
     */
    public function successMessage(?string $message): self
    {
        $this->payload['successMessage'] = $message;

        return $this;
    }

    /**
     * 设置 request 结构化事件失败后的提示文案。
     */
    public function errorMessage(?string $message): self
    {
        $this->payload['errorMessage'] = $message;

        return $this;
    }

    /**
     * 设置 request 结构化事件执行中的 loading 文案。
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
