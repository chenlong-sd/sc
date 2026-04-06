<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\StructuredEventInspector;

final class ListWidget implements Renderable, EventAware
{
    use HasEvents {
        on as private bindListEventHandler;
    }
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = ['reload', 'filterSubmit', 'filterReset'];

    private ?Form $filterForm = null;
    private ?Table $table = null;
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
     */
    public function filters(Form $filterForm): self
    {
        $this->filterForm = $filterForm;

        return $this;
    }

    /**
     * 设置列表主体表格。
     */
    public function table(Table $table): self
    {
        $this->table = $table;

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
}
