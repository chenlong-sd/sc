<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class ActionRenderTarget
{
    use EncodesJsValues;

    public function __construct(
        private readonly ?string $tableKey = null,
        private readonly ?string $listKey = null,
    ) {
    }

    public static function resolve(Action $action, ?TableRenderBindings $fallbackTableBindings = null): self
    {
        if ($action->tableTarget() !== null && $action->tableTarget() !== '') {
            return new self($action->tableTarget(), null);
        }

        if ($action->listTarget() !== null && $action->listTarget() !== '') {
            return new self(null, $action->listTarget());
        }

        if ($fallbackTableBindings !== null) {
            return new self($fallbackTableBindings->tableKey(), null);
        }

        return new self();
    }

    public function tableKey(): ?string
    {
        return $this->tableKey;
    }

    public function listKey(): ?string
    {
        return $this->listKey;
    }

    public function hasDataTarget(): bool
    {
        return ($this->tableKey !== null && $this->tableKey !== '')
            || ($this->listKey !== null && $this->listKey !== '');
    }

    public function loadingExpression(): string
    {
        if ($this->tableKey !== null) {
            return sprintf(
                '(tableStates[%s]?.loading || false)',
                $this->jsString($this->tableKey)
            );
        }

        if ($this->listKey !== null) {
            return sprintf(
                '(tableStates[resolveListTableKey(%s)]?.loading || false)',
                $this->jsString($this->listKey)
            );
        }

        return 'false';
    }

    public function reloadExpression(): string
    {
        if ($this->tableKey !== null) {
            return sprintf('loadTableData(%s)', $this->jsString($this->tableKey));
        }

        if ($this->listKey !== null) {
            return sprintf('reloadList(%s)', $this->jsString($this->listKey));
        }

        throw new InvalidArgumentException('Action render target requires explicit table/list target for reload expression.');
    }

    public function deleteSelectionExpression(string $confirmTextExpression): string
    {
        if ($this->tableKey !== null) {
            return sprintf(
                'deleteTableSelection(%s, %s)',
                $this->jsString($this->tableKey),
                $confirmTextExpression
            );
        }

        if ($this->listKey !== null) {
            return sprintf(
                'deleteListSelection(%s, %s)',
                $this->jsString($this->listKey),
                $confirmTextExpression
            );
        }

        throw new InvalidArgumentException('Action render target requires explicit table/list target for delete expression.');
    }

    public function openDialogExpression(string $dialogKey, ?string $rowExpression = null): string
    {
        if ($this->tableKey !== null) {
            return sprintf(
                'openDialog(%s, %s, %s)',
                $this->jsString($dialogKey),
                $rowExpression ?? 'null',
                $this->jsString($this->tableKey)
            );
        }

        if ($this->listKey !== null) {
            return sprintf(
                'openDialog(%s, %s, resolveListTableKey(%s))',
                $this->jsString($dialogKey),
                $rowExpression ?? 'null',
                $this->jsString($this->listKey)
            );
        }

        if ($rowExpression !== null) {
            return sprintf('openDialog(%s, %s)', $this->jsString($dialogKey), $rowExpression);
        }

        return sprintf('openDialog(%s)', $this->jsString($dialogKey));
    }

    public function loadingKeySuffix(): ?string
    {
        if ($this->tableKey !== null) {
            return 'table:' . $this->tableKey;
        }

        if ($this->listKey !== null) {
            return 'list:' . $this->listKey;
        }

        return null;
    }
}
