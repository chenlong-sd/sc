<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class TableRenderer
{
    use EncodesJsValues;

    private const DRAG_SORT_HANDLE_CLASS = 'sc-v2-table-drag-handle';

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

        foreach ($table->getToolbarActions() as $action) {
            $left->append($this->actionButtonRenderer->render($action, false, 'default', $bindings, $renderContext, 'page-header'));
        }

        if ($left->getChildren()) {
            $toolbar->append($left);
        }

        if ($table->useExport()) {
            $attrs = [
                'type' => $table->getExportType(),
                'bg' => '',
                'text' => '',
                ':loading' => $bindings->exportLoadingExpression(),
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

        if ($table->hasSelection() && !$table->hasExplicitSelectionColumn()) {
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

        foreach ($table->columns() as $column) {
            if (!$column->isRenderable()) {
                continue;
            }

            $element->append($this->columnRenderer->render($column, $bindings, $table->useSettings()));
        }

        if ($table->getRowActions() || $table->useDragSort()) {
            $element->append($this->renderRowActionColumn($table, $bindings, $renderContext));
        }

        return $element;
    }

    public function renderSettingsDialog(Table $table, TableRenderBindings $bindings): DoubleLabel
    {
        $dialog = El::double('el-dialog')->addClass('sc-v2-table-settings-dialog')->setAttrs([
            'title' => '列设置',
            'width' => '760px',
            'append-to-body' => '',
            ':model-value' => $bindings->settingsVisibleExpression(),
            '@close' => $bindings->closeSettingsExpression(),
            '@update:model-value' => $bindings->updateSettingsVisibleExpression(),
        ]);

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

        $settingsTable = El::double('el-table')->setAttrs([
            ':data' => $bindings->settingsDraftColumnsExpression(),
            'border' => '',
            'size' => 'small',
            'style' => 'width:100%',
        ]);

        $settingsTable->append(
            El::double('el-table-column')->setAttrs([
                'label' => '列名称',
                'prop' => 'label',
                'min-width' => '160',
            ]),
            $this->renderSettingsShowColumn(),
            $this->renderSettingsWidthColumn(),
            $this->renderSettingsFixedColumn(),
            $this->renderSettingsAlignColumn()
        );

        $body->append($switches, $settingsTable);
        $dialog->append($body);
        $dialog->append(
            El::double('template')->setAttr('#footer')->append(
                El::double('div')->addClass('sc-v2-table-settings__footer')->append(
                    El::double('div')->addClass('sc-v2-table-settings__footer-actions')->append(
                        El::double('el-button')->setAttr('@click', $bindings->closeSettingsExpression())->append('取消'),
                        El::double('el-button')->setAttr('@click', $bindings->resetSettingsDraftExpression())->append('恢复默认'),
                        El::double('el-button')->setAttrs([
                            'type' => 'primary',
                            '@click' => $bindings->saveSettingsExpression(),
                        ])->append('保存设置')
                    )
                )
            )
        );

        return $dialog;
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
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $actionColumn = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'fixed' => 'right',
            'align' => 'center',
            'width' => $table->getRowActionColumnWidth() ?? max(
                120,
                (count($table->getRowActions()) + ($table->useDragSort() ? 1 : 0)) * 76
            ),
        ]);

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

    private function renderSettingsShowColumn(): AbstractHtmlElement
    {
        return El::double('el-table-column')->setAttrs([
            'label' => '展示',
            'width' => '100',
            'align' => 'center',
        ])->append(
            El::double('template')->setAttr('#default', 'scope')->append(
                El::double('el-switch')->setAttr('v-model', 'scope.row.show')
            )
        );
    }

    private function renderSettingsWidthColumn(): AbstractHtmlElement
    {
        return El::double('el-table-column')->setAttrs([
            'label' => '宽度',
            'width' => '140',
            'align' => 'center',
        ])->append(
            El::double('template')->setAttr('#default', 'scope')->append(
                El::double('el-input')->setAttrs([
                    'v-model' => 'scope.row.width',
                    'clearable' => '',
                    'placeholder' => '自动',
                    'style' => 'width:100px',
                ])
            )
        );
    }

    private function renderSettingsFixedColumn(): AbstractHtmlElement
    {
        return El::double('el-table-column')->setAttrs([
            'label' => '固定位置',
            'width' => '140',
            'align' => 'center',
        ])->append(
            El::double('template')->setAttr('#default', 'scope')->append(
                El::double('el-select')->setAttrs([
                    'v-model' => 'scope.row.fixed',
                    'clearable' => '',
                    'placeholder' => '默认',
                    'style' => 'width:110px',
                ])->append(
                    El::double('el-option')->setAttrs([
                        'label' => '左侧',
                        'value' => 'left',
                    ]),
                    El::double('el-option')->setAttrs([
                        'label' => '右侧',
                        'value' => 'right',
                    ])
                )
            )
        );
    }

    private function renderSettingsAlignColumn(): AbstractHtmlElement
    {
        return El::double('el-table-column')->setAttrs([
            'label' => '对齐',
            'width' => '140',
            'align' => 'center',
        ])->append(
            El::double('template')->setAttr('#default', 'scope')->append(
                El::double('el-select')->setAttrs([
                    'v-model' => 'scope.row.align',
                    'clearable' => '',
                    'placeholder' => '默认',
                    'style' => 'width:110px',
                ])->append(
                    El::double('el-option')->setAttrs([
                        'label' => '左对齐',
                        'value' => 'left',
                    ]),
                    El::double('el-option')->setAttrs([
                        'label' => '居中对齐',
                        'value' => 'center',
                    ]),
                    El::double('el-option')->setAttrs([
                        'label' => '右对齐',
                        'value' => 'right',
                    ])
                )
            )
        );
    }
}
