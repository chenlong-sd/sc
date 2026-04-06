<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class TableRenderBindings
{
    use EncodesJsValues;

    public function __construct(
        private readonly string $tableKey,
        private readonly string $stateStore = 'tableStates',
    ) {
    }

    public function tableKey(): string
    {
        return $this->tableKey;
    }

    public function tableKeyLiteral(): string
    {
        return $this->jsString($this->tableKey);
    }

    public function stateExpression(): string
    {
        return sprintf('%s[%s]', $this->stateStore, $this->tableKeyLiteral());
    }

    public function rowsExpression(): string
    {
        return sprintf('(%s?.rows || [])', $this->stateExpression());
    }

    public function loadingExpression(): string
    {
        return sprintf('(%s?.loading || false)', $this->stateExpression());
    }

    public function totalExpression(): string
    {
        return sprintf('(%s?.total || 0)', $this->stateExpression());
    }

    public function pageExpression(): string
    {
        return sprintf('(%s?.page || 1)', $this->stateExpression());
    }

    public function pageSizeExpression(): string
    {
        return sprintf('(%s?.pageSize || 20)', $this->stateExpression());
    }

    public function selectionExpression(): string
    {
        return sprintf('(%s?.selection || [])', $this->stateExpression());
    }

    public function reloadExpression(): string
    {
        return sprintf('loadTableData(%s)', $this->tableKeyLiteral());
    }

    public function selectionChangeExpression(): string
    {
        return sprintf('handleTableSelectionChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function sortChangeExpression(): string
    {
        return sprintf('handleTableSortChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function pageChangeExpression(): string
    {
        return sprintf('handleTablePageChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function pageSizeChangeExpression(): string
    {
        return sprintf('handleTablePageSizeChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function deleteSelectionExpression(string $confirmTextExpression): string
    {
        return sprintf(
            'deleteTableSelection(%s, %s)',
            $this->tableKeyLiteral(),
            $confirmTextExpression
        );
    }

    public function openDialogExpression(string $dialogKey, ?string $rowExpression = null): string
    {
        return sprintf(
            'openDialog(%s, %s, %s)',
            $this->jsString($dialogKey),
            $rowExpression ?? 'null',
            $this->tableKeyLiteral()
        );
    }
}
