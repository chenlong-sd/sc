<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\AppliesRenderableAttributes;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class TableRenderer
{
    use AppliesRenderableAttributes;
    use EncodesJsValues;

    private const RESERVED_TABLE_ROOT_ATTRS = [
        'ref',
        ':data',
        'v-loading',
        ':stripe',
        ':border',
        'empty-text',
        '@selection-change',
        '@sort-change',
        'row-key',
        ':tree-props',
        ':max-height',
    ];
    private const DRAG_SORT_HANDLE_CLASS = 'sc-v2-table-drag-handle';
    private const SETTINGS_DRAG_HANDLE_CLASS = 'sc-v2-table-settings-drag-handle';

    public function __construct(
        private readonly ColumnRenderer $columnRenderer,
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function renderToolbar(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        if ($table->useExport()) {
            $renderContext?->document()->assets()->addScript(StaticResource::XLSX);
        }

        $toolbar = El::double('div')->addClass('sc-v2-toolbar');
        $left = El::double('div')->addClass('sc-v2-toolbar__actions');
        $right = El::double('div')->addClass('sc-v2-toolbar__tools');
        $trashModeExpression = $bindings->trashModeExpression();

        foreach ($table->getToolbarLeftActions() as $action) {
            $left->append($this->renderToolbarAction($table, $bindings, $action, $renderContext, $trashModeExpression));
        }

        if ($left->getChildren()) {
            $toolbar->append($left);
        }

        foreach ($table->getToolbarRightActions() as $action) {
            $right->append($this->renderToolbarAction($table, $bindings, $action, $renderContext, $trashModeExpression));
        }

        if ($table->useTrash() && $table->getTrashDialog() !== null) {
            $right->append(
                El::double('el-button')->setAttrs([
                    'type' => 'danger',
                    'bg' => '',
                    'text' => '',
                    'icon' => 'Delete',
                    'v-if' => sprintf('!%s', $trashModeExpression),
                    '@click' => $bindings->openDialogExpression($table->getTrashDialogKey(), 'null'),
                ])->append($table->getTrashDialogTitle())
            );
        }

        if ($table->useTrash() && $table->getTrashRecoverUrl() !== null) {
            $right->append(
                El::double('el-button')->setAttrs([
                    'type' => 'success',
                    'bg' => '',
                    'text' => '',
                    'icon' => 'RefreshLeft',
                    'v-if' => $trashModeExpression,
                    ':disabled' => sprintf('(%s.length || 0) === 0', $bindings->selectionExpression()),
                    '@click' => $bindings->recoverSelectionExpression(),
                ])->append('恢复数据')
            );
        }

        if ($table->useExport()) {
            $attrs = [
                'type' => $table->getExportType(),
                'bg' => '',
                'text' => '',
                ':loading' => $bindings->exportLoadingExpression(),
                'v-if' => sprintf('!%s', $trashModeExpression),
                '@click' => $bindings->exportExpression(),
            ];

            if ($table->getExportIcon() !== null) {
                $attrs['icon'] = $table->getExportIcon();
            }

            $right->append(
                El::double('el-button')->setAttrs($attrs)->append($table->getExportLabel())
            );
        }

        if ($table->useSettings()) {
            $right->append(
                El::double('el-button')->setAttrs([
                    'type' => 'info',
                    'bg' => '',
                    'text' => '',
                    'icon' => 'Setting',
                    'v-if' => sprintf('!%s', $trashModeExpression),
                    '@click' => $bindings->openSettingsExpression(),
                ])->append('列设置')
            );
        }

        if ($right->getChildren()) {
            $toolbar->append($right);
        }

        return $toolbar;
    }

    public function renderStatusToggleBar(Table $table, TableRenderBindings $bindings): AbstractHtmlElement
    {
        $container = El::double('div')
            ->addClass('sc-v2-status-toggles')
            ->addClass($table->useStatusTogglesNewLine() ? 'sc-v2-status-toggles--newline' : 'sc-v2-status-toggles--inline');

        foreach ($table->getStatusToggles() as $toggle) {
            $name = is_string($toggle['name'] ?? null) ? trim($toggle['name']) : '';
            if ($name === '') {
                continue;
            }

            $group = El::double('div')->addClass('sc-v2-status-toggle');
            $label = $toggle['label'] ?? null;
            $hasLabel = $label !== null && $label !== '';

            $group->addClass($hasLabel ? 'sc-v2-status-toggle--labeled' : 'sc-v2-status-toggle--plain');

            if ($hasLabel) {
                $labelElement = El::double('div')->addClass('sc-v2-status-toggle__label');
                $labelElement->append(
                    El::double('span')->addClass('sc-v2-status-toggle__label-text')->append($label),
                    El::double('span')->addClass('sc-v2-status-toggle__label-colon')->append('：')
                );
                $group->append($labelElement);
            }

            $buttonGroup = El::double('el-button-group')->addClass('sc-v2-status-toggle__buttons');
            $buttonGroup->append($this->renderStatusToggleButton($bindings, $name, null, '全部', $hasLabel));

            foreach ((array)($toggle['options'] ?? []) as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $buttonGroup->append($this->renderStatusToggleButton(
                    $bindings,
                    $name,
                    $option['value'] ?? null,
                    (string)($option['label'] ?? ($option['value'] ?? '')),
                    $hasLabel
                ));
            }

            $group->append($buttonGroup);
            $container->append($group);
        }

        return $container;
    }

    public function renderTable(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        if (($table->isTree() || $table->useDragSort()) && $table->getRowKey() === null) {
            throw new InvalidArgumentException(sprintf(
                'Table [%s] uses tree() or dragSort() but rowKey() is not configured.',
                $table->key()
            ));
        }

        $element = El::double('el-table')->setAttrs([
            'ref' => $bindings->tableKey(),
            ':data' => $bindings->rowsExpression(),
            'v-loading' => $bindings->loadingExpression(),
            ':stripe' => $table->useSettings() ? $bindings->stripeExpression($table->useStripe()) : ($table->useStripe() ? 'true' : 'false'),
            ':border' => $table->useSettings() ? $bindings->borderExpression($table->useBorder()) : ($table->useBorder() ? 'true' : 'false'),
            'empty-text' => $table->getEmptyText(),
            'style' => 'width: 100%',
            '@selection-change' => $bindings->selectionChangeExpression(),
            '@sort-change' => $bindings->sortChangeExpression(),
        ]);

        if ($table->getRowKey() !== null) {
            $element->setAttr('row-key', $table->getRowKey());
        }

        if ($table->isTree()) {
            $element->setAttr(':tree-props', $this->jsValue($table->getTreeProps()));
        }

        if ($table->getMaxHeight() !== 0) {
            $element->setAttr(':max-height', $bindings->maxHeightExpression());
        }

        $element = $this->applyRenderableAttributes(
            $element,
            $this->filterRenderableTableRootAttributes($table->getRenderAttributes())
        );

        if (!$table->useSettings() && $table->hasSelection() && !$table->hasExplicitSelectionColumn()) {
            $selectionColumn = El::double('el-table-column')->setAttrs([
                'type' => 'selection',
                'width' => '48',
                'align' => 'center',
            ]);
            if ($table->getSelectionFixed() !== null) {
                $selectionColumn->setAttr('fixed', $table->getSelectionFixed());
            }

            $element->append($selectionColumn);
        }

        $renderableColumns = array_values(array_filter(
            $table->columns(),
            static fn($column) => $column->isRenderable()
        ));

        if ($table->useSettings()) {
            $settingsEntriesByColumnId = [];
            $leadingSettingsEntries = [];
            $rowActionSettingsEntry = null;

            foreach ($table->getSettingsColumnDefinitions() as $settingsEntry) {
                $settingsColumn = $settingsEntry['column'] ?? null;
                if ($settingsColumn instanceof Column) {
                    $settingsEntriesByColumnId[spl_object_id($settingsColumn)] = $settingsEntry;
                    continue;
                }

                if (($settingsEntry['kind'] ?? null) === 'selection') {
                    $leadingSettingsEntries[] = $settingsEntry;
                    continue;
                }

                if (($settingsEntry['kind'] ?? null) === 'row_actions') {
                    $rowActionSettingsEntry = $settingsEntry;
                }
            }

            $orderedEntries = $leadingSettingsEntries;

            foreach ($renderableColumns as $column) {
                $settingsEntry = $settingsEntriesByColumnId[spl_object_id($column)] ?? null;
                if ($settingsEntry !== null) {
                    $orderedEntries[] = $settingsEntry;
                    continue;
                }

                if ($orderedEntries !== []) {
                    $element->append($this->renderOrderedSettingsColumns($table, $orderedEntries, $bindings, $renderContext));
                    $orderedEntries = [];
                }

                $element->append($this->columnRenderer->render($column, $bindings, true));
            }

            if ($rowActionSettingsEntry !== null) {
                $orderedEntries[] = $rowActionSettingsEntry;
            }

            if ($orderedEntries !== []) {
                $element->append($this->renderOrderedSettingsColumns($table, $orderedEntries, $bindings, $renderContext));
            }
        } else {
            foreach ($renderableColumns as $column) {
                $element->append($this->columnRenderer->render($column, $bindings, false));
            }

            if ($table->hasManagedRowActionColumn()) {
                $element->append($this->renderRowActionColumn($table, $bindings, $renderContext));
            }
        }

        return $element;
    }

    public function renderSettingsDialog(Table $table, TableRenderBindings $bindings): DoubleLabel
    {
        $body = El::double('div')->addClass('sc-v2-table-settings');
        $switches = El::double('div')->addClass('sc-v2-table-settings__switches');
        $stateExpression = $bindings->stateExpression();

        $switches->append(
            El::double('div')->addClass('sc-v2-table-settings__switch')->append(
                El::double('span')->append('表格分割线'),
                El::double('el-switch')->setAttr('v-model', $stateExpression . '.settingsDraft.border')
            ),
            El::double('div')->addClass('sc-v2-table-settings__switch')->append(
                El::double('span')->append('表格斑马纹'),
                El::double('el-switch')->setAttr('v-model', $stateExpression . '.settingsDraft.stripe')
            )
        );

        if ($table->useExport()) {
            $tabs = El::double('el-tabs')->setAttrs([
                'v-model' => $bindings->settingsTabModelExpression(),
                '@update:model-value' => $bindings->syncSettingsSortExpression(),
            ]);

            $displayPane = El::double('el-tab-pane')->setAttrs([
                'label' => '展示设置',
                'name' => 'display',
            ])->append(
                El::double('div')->addClass('sc-v2-table-settings__pane')->append(
                    $switches,
                    $this->renderSettingsTable($bindings, 'display', false)
                )
            );

            $exportPane = El::double('el-tab-pane')->setAttrs([
                'label' => '导出设置',
                'name' => 'export',
                'lazy' => '',
            ])->append(
                El::double('div')->addClass('sc-v2-table-settings__pane')->append(
                    $this->renderSettingsTable($bindings, 'export', true)
                )
            );

            $tabs->append($displayPane, $exportPane);
            $body->append($tabs);
        } else {
            $body->append(
                $switches,
                $this->renderSettingsTable($bindings, 'display', false)
            );
        }

        $footer = El::double('div')->addClass('sc-v2-table-settings__footer')->append(
            El::double('div')->addClass('sc-v2-table-settings__footer-actions')->append(
                El::double('el-button')->setAttr('@click', $bindings->closeSettingsExpression())->append('取消'),
                El::double('el-button')->setAttr('@click', $bindings->resetSettingsDraftExpression())->append('恢复默认'),
                El::double('el-button')->setAttrs([
                    'type' => 'primary',
                    '@click' => $bindings->saveSettingsExpression(),
                ])->append('保存设置')
            )
        );

        $panel = El::double('div')
            ->addClass('sc-v2-table-settings-modal__panel')
            ->addClass($table->useExport() ? 'sc-v2-table-settings-modal__panel--wide' : 'sc-v2-table-settings-modal__panel--normal')
            ->append(
                El::double('div')->addClass('sc-v2-table-settings-modal__header')->append(
                    El::double('div')->addClass('sc-v2-table-settings-modal__title')->append('列设置'),
                    El::double('el-button')->setAttrs([
                        'link' => '',
                        'type' => 'info',
                        'icon' => 'Close',
                        '@click' => $bindings->closeSettingsExpression(),
                    ])
                ),
                El::double('div')->addClass('sc-v2-table-settings-modal__body')->append($body),
                El::double('div')->addClass('sc-v2-table-settings-modal__footer')->append($footer)
            );

        return El::double('teleport')->setAttr('to', 'body')->append(
            El::double('transition')->setAttr('name', 'dialog-fade')->append(
                El::double('div')->addClass('sc-v2-table-settings-modal')->setAttrs([
                    'v-show' => $bindings->settingsVisibleExpression(),
                    '@click.self' => $bindings->closeSettingsExpression(),
                ])->append($panel)
            )
        );
    }

    public function renderPagination(Table $table, TableRenderBindings $bindings): AbstractHtmlElement
    {
        return El::double('div')->setAttr('style', 'display:flex;justify-content:flex-end')
            ->append(
                El::double('el-pagination')->setAttrs([
                    'background' => '',
                    'layout' => 'total, sizes, prev, pager, next, jumper',
                    ':current-page' => $bindings->pageExpression(),
                    ':page-size' => $bindings->pageSizeExpression(),
                    ':page-sizes' => $this->jsValue($table->getPageSizes()),
                    ':total' => $bindings->totalExpression(),
                    '@size-change' => $bindings->pageSizeChangeExpression(),
                    '@current-change' => $bindings->pageChangeExpression(),
                ])
            );
    }

    private function renderRowActionColumn(
        Table $table,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null,
        ?string $settingsKey = null,
        bool $settingsEnabled = false
    ): AbstractHtmlElement
    {
        $defaultWidth = $this->resolveRowActionColumnWidth($table);
        $attrs = [
            'label' => '操作',
        ];

        if ($settingsEnabled && is_string($settingsKey) && $settingsKey !== '') {
            $attrs[':width'] = $bindings->columnWidthExpression($settingsKey, $defaultWidth);
            $attrs[':align'] = $bindings->columnAlignExpression($settingsKey, 'center');
            $attrs[':fixed'] = $bindings->columnFixedExpression($settingsKey, 'right');
        } else {
            $attrs['fixed'] = 'right';
            $attrs['align'] = 'center';
            $attrs['width'] = $defaultWidth;
        }

        $actionColumn = El::double('el-table-column')->setAttrs($attrs);

        if ($settingsEnabled && is_string($settingsKey) && $settingsKey !== '') {
            $this->appendVisibilityCondition($actionColumn, $bindings->columnVisibleExpression($settingsKey));
        }

        if ($table->useTrash()) {
            $this->appendVisibilityCondition($actionColumn, sprintf('!%s', $bindings->trashModeExpression()));
        }

        $template = El::double('template')->setAttr('#default', 'scope');
        $actions = El::double('div')->addClass('sc-v2-row-actions');
        if ($table->useDragSort()) {
            $actions->append($this->renderDragSortHandle($table));
        }
        foreach ($table->getRowActions() as $action) {
            $actions->append($this->actionButtonRenderer->render($action, true, 'default', $bindings, $renderContext));
        }
        $template->append($actions);
        $actionColumn->append($template);

        return $actionColumn;
    }

    private function resolveRowActionColumnWidth(Table $table): int
    {
        return $table->getRowActionColumnWidth() ?? max(
            120,
            (count($table->getRowActions()) + ($table->useDragSort() ? 1 : 0)) * 76
        );
    }

    private function renderDragSortHandle(Table $table): AbstractHtmlElement
    {
        $attrs = [
            'type' => $table->getDragSortType(),
            'size' => 'default',
            'link' => '',
            'class' => self::DRAG_SORT_HANDLE_CLASS,
        ];

        if ($table->getDragSortIcon() !== null) {
            $attrs['icon'] = $table->getDragSortIcon();
        }

        return El::double('el-button')
            ->setAttrs($attrs)
            ->append($table->getDragSortLabel());
    }

    /**
     * @param array<string, mixed> $attrs
     * @return array<string, mixed>
     */
    private function filterRenderableTableRootAttributes(array $attrs): array
    {
        $filtered = [];

        foreach ($attrs as $attr => $value) {
            $name = is_string($attr) ? trim($attr) : '';
            if ($name === '' || in_array($name, self::RESERVED_TABLE_ROOT_ATTRS, true)) {
                continue;
            }

            $filtered[$name] = $value;
        }

        return $filtered;
    }

    private function renderToolbarAction(
        Table $table,
        TableRenderBindings $bindings,
        Action $action,
        ?RenderContext $renderContext,
        string $trashModeExpression
    ): AbstractHtmlElement {
        $button = $this->actionButtonRenderer->render($action, false, 'default', $bindings, $renderContext, 'page-header');

        if ($table->useTrash() && !$this->keepToolbarActionVisibleInTrash($action)) {
            $this->appendVisibilityCondition($button, sprintf('!%s', $trashModeExpression));
        }

        return $button;
    }

    private function keepToolbarActionVisibleInTrash(Action $action): bool
    {
        return $action->intent() === ActionIntent::REFRESH;
    }

    private function appendVisibilityCondition(AbstractHtmlElement $element, string $condition): void
    {
        $current = trim((string)$element->getAttr('v-if', ''));
        if ($current === '') {
            $element->setAttr('v-if', $condition);

            return;
        }

        $element->setAttr('v-if', sprintf('(%s) && (%s)', $current, $condition));
    }

    private function renderStatusToggleButton(
        TableRenderBindings $bindings,
        string $name,
        mixed $value,
        string $label,
        bool $hasGroupLabel = false
    ): AbstractHtmlElement {
        $activeExpression = $bindings->statusToggleActiveExpression($name, $value);
        $attrs = [
            '@click' => $bindings->statusToggleClickExpression($name, $value),
        ];

        if ($hasGroupLabel) {
            $attrs['text'] = '';
            $attrs[':type'] = $this->jsLiteralTernary($activeExpression, 'primary', '');
            $attrs[':plain'] = sprintf('!%s', $activeExpression);
        } else {
            $attrs['type'] = 'primary';
            $attrs['bg'] = '';
            $attrs[':plain'] = sprintf('!%s', $activeExpression);
        }

        return El::double('el-button')->setAttrs($attrs)->append($label);
    }

    private function renderSettingsTable(
        TableRenderBindings $bindings,
        string $mode,
        bool $exportMode
    ): AbstractHtmlElement {
        $list = El::double('div')
            ->addClass('sc-v2-table-settings-list')
            ->addClass($exportMode ? 'sc-v2-table-settings-list--export' : 'sc-v2-table-settings-list--display')
            ->setAttrs([
                'style' => 'width:100%',
                'data-sc-table-settings-key' => $bindings->tableKey(),
                'data-sc-table-settings-mode' => $mode,
            ]);

        $header = El::double('div')
            ->addClass('sc-v2-table-settings-list__header')
            ->append(
                $this->renderSettingsListHeaderCell('排序', 'sort'),
                $this->renderSettingsListHeaderCell('列名称', 'name')
            );

        $row = El::double('div')
            ->addClass('sc-v2-table-settings-list__row')
            ->setAttrs([
                'v-for' => sprintf('(row, settingsIndex) in %s', $bindings->settingsVirtualRowsExpression($mode)),
                ':key' => sprintf("(row?.key || ('sc-v2-table-settings-row-' + (%s + settingsIndex)))", $bindings->settingsVirtualStartExpression($mode)),
            ])
            ->append(
                $this->renderSettingsSortCell(),
                $this->renderSettingsLabelCell()
            );

        if ($exportMode) {
            $header->append(
                $this->renderSettingsListHeaderCell('导出', 'toggle')
            );
            $row->append(
                $this->renderSettingsExportCell()
            );
        } else {
            $header->append(
                $this->renderSettingsListHeaderCell('展示', 'toggle'),
                $this->renderSettingsListHeaderCell('宽度', 'width'),
                $this->renderSettingsListHeaderCell('固定位置', 'fixed'),
                $this->renderSettingsListHeaderCell('对齐', 'align')
            );
            $row->append(
                $this->renderSettingsShowCell(),
                $this->renderSettingsWidthCell(),
                $this->renderSettingsFixedCell(),
                $this->renderSettingsAlignCell()
            );
        }

        $rows = El::double('div')
            ->addClass('sc-v2-table-settings-list__rows')
            ->setAttr('data-sc-table-settings-body', '')
            ->append($row);

        $body = El::double('div')
            ->addClass('sc-v2-table-settings-list__body')
            ->setAttrs([
                'data-sc-table-settings-scroll' => '',
                '@scroll.passive' => $bindings->settingsVirtualScrollExpression($mode),
            ])
            ->append(
                El::double('div')->addClass('sc-v2-table-settings-list__spacer')->setAttr(
                    ':style',
                    sprintf("{ height: (%s + 'px') }", $bindings->settingsVirtualTopPaddingExpression($mode))
                ),
                $rows,
                El::double('div')->addClass('sc-v2-table-settings-list__spacer')->setAttr(
                    ':style',
                    sprintf("{ height: (%s + 'px') }", $bindings->settingsVirtualBottomPaddingExpression($mode))
                )
            );

        return $list->append($header, $body);
    }

    private function renderSettingsListHeaderCell(string $label, string $modifier): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--' . $modifier)
            ->append($label);
    }

    private function renderSettingsSortCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--sort')
            ->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'icon' => 'Rank',
                    'class' => self::SETTINGS_DRAG_HANDLE_CLASS,
                ])
            );
    }

    private function renderSettingsLabelCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--name')
            ->append(
                El::double('span')->append('{{ row.label }}')
            );
    }

    private function renderSettingsShowCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--toggle')
            ->append(
                El::double('el-switch')->setAttrs([
                    'v-model' => 'row.show',
                    'size' => 'small',
                ])
            );
    }

    private function renderSettingsWidthCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--width')
            ->append(
                El::double('el-input')->setAttrs([
                    'v-model' => 'row.width',
                    'size' => 'small',
                    'clearable' => '',
                    'placeholder' => '自动',
                ])
            );
    }

    private function renderSettingsExportCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--toggle')
            ->append(
                El::double('el-switch')->setAttrs([
                    'v-model' => 'row.export',
                    'size' => 'small',
                ])
            );
    }

    private function renderSettingsFixedCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--fixed')
            ->append(
                El::double('el-select')->setAttrs([
                    'v-model' => 'row.fixed',
                    'size' => 'small',
                    'clearable' => '',
                    'placeholder' => '默认',
                ])->append(
                    El::double('el-option')->setAttrs([
                        'label' => '左侧',
                        'value' => 'left',
                    ])->append('左侧'),
                    El::double('el-option')->setAttrs([
                        'label' => '右侧',
                        'value' => 'right',
                    ])->append('右侧')
                )
            );
    }

    private function renderSettingsAlignCell(): AbstractHtmlElement
    {
        return El::double('div')
            ->addClass('sc-v2-table-settings-list__cell')
            ->addClass('sc-v2-table-settings-list__cell--align')
            ->append(
                El::double('el-select')->setAttrs([
                    'v-model' => 'row.align',
                    'size' => 'small',
                    'clearable' => '',
                    'placeholder' => '默认',
                ])->append(
                    El::double('el-option')->setAttrs([
                        'label' => '左对齐',
                        'value' => 'left',
                    ])->append('左对齐'),
                    El::double('el-option')->setAttrs([
                        'label' => '居中对齐',
                        'value' => 'center',
                    ])->append('居中对齐'),
                    El::double('el-option')->setAttrs([
                        'label' => '右对齐',
                        'value' => 'right',
                    ])->append('右对齐')
                )
            );
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     */
    private function renderOrderedSettingsColumns(
        Table $table,
        array $columns,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $wrapper = El::double('template')->setAttrs([
            'v-for' => sprintf('(renderColumnKey, renderColumnIndex) in %s', $bindings->renderColumnKeysExpression()),
            ':key' => "'sc-v2-col-' + renderColumnKey + '-' + renderColumnIndex",
        ]);

        foreach ($columns as $index => $column) {
            $branchDirective = $index === 0 ? 'v-if' : 'v-else-if';
            $columnKey = (string)($column['key'] ?? '');
            $definitionColumn = $column['column'] ?? null;
            $wrapper->append(
                El::double('template')->setAttr(
                    $branchDirective,
                    sprintf('renderColumnKey === %s', $this->jsValue($columnKey))
                )->append(
                    $definitionColumn instanceof Column
                        ? $this->columnRenderer->render($definitionColumn, $bindings, true, $columnKey)
                        : $this->renderSyntheticSettingsColumn(
                            $table,
                            (string)($column['kind'] ?? ''),
                            $bindings,
                            $renderContext,
                            $columnKey
                        )
                )
            );
        }

        return $wrapper;
    }

    private function renderSyntheticSettingsColumn(
        Table $table,
        string $kind,
        TableRenderBindings $bindings,
        ?RenderContext $renderContext,
        string $columnKey
    ): AbstractHtmlElement {
        return match ($kind) {
            'selection' => $this->columnRenderer->render(
                $this->buildAutoSelectionColumn($table),
                $bindings,
                true,
                $columnKey
            ),
            default => $this->renderRowActionColumn($table, $bindings, $renderContext, $columnKey, true),
        };
    }

    private function buildAutoSelectionColumn(Table $table): Column
    {
        $column = Column::selection();
        if ($table->getSelectionFixed() !== null) {
            $column->fixed($table->getSelectionFixed());
        }

        return $column;
    }
}
