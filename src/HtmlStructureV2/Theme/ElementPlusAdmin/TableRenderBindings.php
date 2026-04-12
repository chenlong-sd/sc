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

    public function exportLoadingExpression(): string
    {
        return sprintf('(%s?.exporting || false)', $this->stateExpression());
    }

    public function maxHeightExpression(): string
    {
        return sprintf('(%s?.maxHeight || null)', $this->stateExpression());
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

    public function exportExpression(): string
    {
        return sprintf('exportTableData(%s)', $this->tableKeyLiteral());
    }

    public function statusToggleActiveExpression(string $name, mixed $value = null): string
    {
        return sprintf(
            'isTableStatusToggleActive(%s, %s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($name),
            $this->jsValue($value)
        );
    }

    public function statusToggleClickExpression(string $name, mixed $value = null): string
    {
        return sprintf(
            'setTableStatusToggle(%s, %s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($name),
            $this->jsValue($value)
        );
    }

    public function selectionChangeExpression(): string
    {
        return sprintf('handleTableSelectionChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function sortChangeExpression(): string
    {
        return sprintf('handleTableSortChange(%s, $event)', $this->tableKeyLiteral());
    }

    public function switchChangeExpression(string $columnKey, string $configExpression): string
    {
        return sprintf(
            'handleTableSwitchChange(%s, scope.row, %s, $event, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($columnKey),
            $configExpression
        );
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

    public function trashModeExpression(): string
    {
        return sprintf('isTableTrashMode(%s)', $this->tableKeyLiteral());
    }

    public function recoverSelectionExpression(): string
    {
        return sprintf('recoverTableSelection(%s)', $this->tableKeyLiteral());
    }

    public function settingsVisibleExpression(): string
    {
        return sprintf('(%s?.settingsVisible || false)', $this->stateExpression());
    }

    public function settingsDraftColumnsExpression(): string
    {
        return sprintf('(%s?.settingsDraft?.columns || [])', $this->stateExpression());
    }

    public function settingsDraftTableDataExpression(?string $mode = null): string
    {
        return sprintf(
            'getTableSettingsDraftColumns(%s, %s)',
            $this->tableKeyLiteral(),
            $this->jsValue($mode)
        );
    }

    public function settingsTabModelExpression(): string
    {
        return sprintf('%s.settingsTab', $this->stateExpression());
    }

    public function renderColumnKeysExpression(): string
    {
        return sprintf('getTableRenderColumnKeys(%s)', $this->tableKeyLiteral());
    }

    public function stripeExpression(bool $fallback): string
    {
        return sprintf('getTableStripe(%s, %s)', $this->tableKeyLiteral(), $this->jsValue($fallback));
    }

    public function borderExpression(bool $fallback): string
    {
        return sprintf('getTableBorder(%s, %s)', $this->tableKeyLiteral(), $this->jsValue($fallback));
    }

    public function columnVisibleExpression(string $columnKey): string
    {
        return sprintf(
            'getTableColumnVisible(%s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($columnKey)
        );
    }

    public function columnWidthExpression(string $columnKey, mixed $fallback = null): string
    {
        return sprintf(
            'getTableColumnWidth(%s, %s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($columnKey),
            $this->jsValue($fallback)
        );
    }

    public function columnAlignExpression(string $columnKey, mixed $fallback = null): string
    {
        return sprintf(
            'getTableColumnAlign(%s, %s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($columnKey),
            $this->jsValue($fallback)
        );
    }

    public function columnFixedExpression(string $columnKey, mixed $fallback = null): string
    {
        return sprintf(
            'getTableColumnFixed(%s, %s, %s)',
            $this->tableKeyLiteral(),
            $this->jsString($columnKey),
            $this->jsValue($fallback)
        );
    }

    public function openSettingsExpression(): string
    {
        return sprintf('openTableSettings(%s)', $this->tableKeyLiteral());
    }

    public function closeSettingsExpression(): string
    {
        return sprintf('closeTableSettings(%s)', $this->tableKeyLiteral());
    }

    public function updateSettingsVisibleExpression(): string
    {
        return sprintf('setTableSettingsDialogVisible(%s, $event)', $this->tableKeyLiteral());
    }

    public function saveSettingsExpression(): string
    {
        return sprintf('saveTableSettings(%s)', $this->tableKeyLiteral());
    }

    public function resetSettingsDraftExpression(): string
    {
        return sprintf('resetTableSettingsDraft(%s)', $this->tableKeyLiteral());
    }

    public function syncSettingsSortExpression(): string
    {
        return sprintf('syncTableSettingsSort(%s)', $this->tableKeyLiteral());
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
