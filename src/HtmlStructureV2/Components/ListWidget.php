<?php

namespace Sc\Util\HtmlStructureV2\Components;

use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\Conditionable;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\ListAutoFilterFormFactory;
use Sc\Util\HtmlStructureV2\Support\StructuredEventInspector;

final class ListWidget implements Renderable, EventAware
{
    use HasEvents {
        on as private bindListEventHandler;
    }
    use Conditionable;
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = ['reload', 'filterSubmit', 'filterReset'];

    private ?Form $filterForm = null;
    private ?Table $table = null;
    private ?Form $resolvedFilterForm = null;
    private bool $resolvedFilterFormInitialized = false;
    /** @var array<string, Dialog> */
    private array $dialogs = [];
    private string $filterTitle = '筛选条件';
    private bool $showSummary = true;

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个复合列表组件实例。
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 设置列表顶部筛选表单。
     * 支持两种写法：
     * - `filters($form)`：直接传完整 Form，适合需要自定义布局/按钮/标签宽度的场景
     * - `filters(Fields::text(...), Fields::select(...))`：直接传字段，内部会自动包装成 inline Form
     *
     * 显式传入时会保留这份 UI；若表格里还声明了
     * `Column::searchable()` / `Table::search()` / `Table::searchSchema()`，
     * V2 会继续自动补齐这份表单里尚未声明的筛选项，减少重复书写。
     */
    public function filters(Form|Field ...$filters): self
    {
        if ($filters === []) {
            return $this;
        }

        $this->filterForm = $this->normalizeFilterForm($filters);
        $this->resolvedFilterForm = null;
        $this->resolvedFilterFormInitialized = false;

        return $this;
    }

    /**
     * 设置列表主体表格。
     */
    public function table(Table $table): self
    {
        $this->table = $table;
        $this->resolvedFilterForm = null;
        $this->resolvedFilterFormInitialized = false;

        return $this;
    }

    /**
     * 显式挂载当前列表需要托管的弹窗。
     */
    public function dialogs(Dialog ...$dialogs): self
    {
        foreach ($dialogs as $dialog) {
            $this->dialogs[$dialog->key()] = $dialog;
        }

        return $this;
    }

    /**
     * 设置筛选区域标题。
     */
    public function filterTitle(string $title): self
    {
        $this->filterTitle = $title;

        return $this;
    }

    /**
     * 控制是否显示列表摘要信息。
     */
    public function showSummary(bool $showSummary = true): self
    {
        $this->showSummary = $showSummary;

        return $this;
    }

    /**
     * 绑定列表运行时事件。
     * 可用事件：reload / filterSubmit / filterReset。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ listKey, tableKey, filterScope, formConfig, filters, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - listKey / listConfig / tableKey
     * - filterScope / formConfig
     * - filters: 当前筛选表单值
     * - vm: 当前 Vue 实例
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindListEventHandler($event, $handler);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getFilterForm(): ?Form
    {
        return $this->filterForm;
    }

    /**
     * 获取当前列表实际生效的筛选表单。
     * 若显式配置了 filters()，会优先保留显式字段，再按表格搜索协议补齐缺失项；
     * 若未配置 filters()，则直接自动推导一份行内筛选表单。
     * 其中自动推导出来的筛选项默认隐藏 label，只保留 placeholder；
     * `displayMapping()` / `displayTag()` / `displayBoolean*()` 会优先识别为 select。
     */
    public function resolveFilterForm(): ?Form
    {
        if ($this->resolvedFilterFormInitialized) {
            return $this->resolvedFilterForm;
        }

        $this->resolvedFilterFormInitialized = true;
        $this->resolvedFilterForm = $this->buildResolvedFilterForm();

        return $this->resolvedFilterForm;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getFilterTitle(): string
    {
        return $this->filterTitle;
    }

    public function shouldShowSummary(): bool
    {
        return $this->showSummary;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'reload' => '列表执行 reloadList() 时触发，返回 false 可取消刷新。',
            'filterSubmit' => '筛选表单提交时触发，可读取 filters / listKey / tableKey。',
            'filterReset' => '筛选表单重置时触发，可读取 filters / listKey / tableKey。',
        ];
    }

    /**
     * @return Dialog[]
     */
    public function getDialogs(): array
    {
        $dialogs = [];

        if ($this->table !== null) {
            $this->collectDialogsFromActions($dialogs, $this->table->getToolbarActions());
            $this->collectDialogsFromActions($dialogs, $this->table->getRowActions());
        }

        foreach ($this->dialogs as $key => $dialog) {
            $dialogs[$key] = $dialog;
        }

        return array_values($dialogs);
    }

    /**
     * @param Action[] $actions
     * @param array<string, Dialog> $dialogs
     */
    private function collectDialogsFromActions(array &$dialogs, array $actions): void
    {
        foreach ($actions as $action) {
            if ($action instanceof Action && $action->hasEventHandlers()) {
                foreach ((new StructuredEventInspector())->collectDialogsFromEventMap($action->getEventHandlers()) as $dialog) {
                    $dialogs[$dialog->key()] = $dialog;
                }
            }

            if (!$action instanceof DialogAction) {
                continue;
            }

            $dialog = $action->getDialog();
            if ($dialog === null) {
                continue;
            }

            $dialogs[$dialog->key()] ??= $dialog;
        }
    }

    private function buildResolvedFilterForm(): ?Form
    {
        $autoFilterForm = $this->table === null
            ? null
            : (new ListAutoFilterFormFactory())->build($this->key, $this->table);

        if ($this->filterForm === null) {
            return $autoFilterForm;
        }

        if ($autoFilterForm === null) {
            return $this->filterForm;
        }

        $mergedForm = clone $this->filterForm;
        $existingFieldNames = array_map(
            static fn (Field $field): string => $field->name(),
            $mergedForm->fields()
        );

        $missingFields = array_values(array_filter(
            $autoFilterForm->fields(),
            static fn (Field $field): bool => !in_array($field->name(), $existingFieldNames, true)
        ));

        if ($missingFields !== []) {
            $mergedForm->addFields(...$missingFields);
        }

        return $mergedForm;
    }

    /**
     * @param array<int, Form|Field> $filters
     */
    private function normalizeFilterForm(array $filters): Form
    {
        if (count($filters) === 1 && $filters[0] instanceof Form) {
            return $filters[0];
        }

        foreach ($filters as $filter) {
            if ($filter instanceof Form) {
                throw new InvalidArgumentException(
                    'ListWidget::filters() cannot mix Form with direct Field arguments.'
                );
            }
        }

        return Form::make($this->key . '-filters')
            ->inline()
            ->addFields(...$filters);
    }
}
