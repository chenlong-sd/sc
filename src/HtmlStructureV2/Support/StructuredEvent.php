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

    public static function openUrl(string|JsExpression $url, array|JsExpression $query = []): self
    {
        return new self('openUrl', [
            'url' => $url,
            'query' => $query,
            'target' => '_self',
        ]);
    }

    public static function openDialog(string|Dialog $dialog): self
    {
        $event = new self('openDialog', [
            'dialogKey' => self::dialogKeyOf($dialog),
        ]);

        return $event->registerDialogTarget($dialog);
    }

    public static function closeDialog(string|Dialog $dialog): self
    {
        $event = new self('closeDialog', [
            'dialogKey' => self::dialogKeyOf($dialog),
        ]);

        return $event->registerDialogTarget($dialog);
    }

    public static function reloadTable(string|Table|null $table = null): self
    {
        $event = new self('reloadTable', [
            'tableKey' => self::tableKeyOf($table),
        ]);

        return $event->registerTableTarget($table);
    }

    public static function reloadList(string|ListWidget|null $list = null): self
    {
        $event = new self('reloadList', [
            'listKey' => self::listKeyOf($list),
        ]);

        return $event->registerListTarget($list);
    }

    public static function reloadPage(): self
    {
        return new self('reloadPage');
    }

    public static function message(string|JsExpression $message, string $type = 'info'): self
    {
        return new self('message', [
            'message' => $message,
            'messageType' => $type,
        ]);
    }

    public static function request(
        string $url,
        string $method = 'post',
        array|JsExpression $query = []
    ): self {
        return new self('request', [
            'url' => $url,
            'method' => strtolower($method) ?: 'post',
            'query' => $query,
            'successMessage' => null,
            'errorMessage' => null,
            'loadingText' => null,
        ]);
    }

    public function target(string $target): self
    {
        $this->payload['target'] = $target;

        return $this;
    }

    public function newTab(): self
    {
        return $this->target('_blank');
    }

    public function features(?string $features): self
    {
        $this->payload['features'] = $features;

        return $this;
    }

    public function row(string|JsExpression|null $row): self
    {
        $this->payload['row'] = $row;

        return $this;
    }

    public function table(string|Table|null $table): self
    {
        $this->payload['tableKey'] = self::tableKeyOf($table);

        return $this->registerTableTarget($table);
    }

    public function list(string|ListWidget|null $list): self
    {
        $this->payload['listKey'] = self::listKeyOf($list);

        return $this->registerListTarget($list);
    }

    public function successMessage(?string $message): self
    {
        $this->payload['successMessage'] = $message;

        return $this;
    }

    public function errorMessage(?string $message): self
    {
        $this->payload['errorMessage'] = $message;

        return $this;
    }

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
}
