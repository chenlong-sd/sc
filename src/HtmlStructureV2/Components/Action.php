<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

class Action implements Renderable, EventAware
{
    use HasEvents {
        on as private bindActionEventHandler;
    }
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = ['click'];

    private string $type = 'default';
    private ?string $icon = null;
    private ?string $key = null;
    private ?string $target = null;
    private ?string $tableTarget = null;
    private ?string $listTarget = null;
    private string|JsExpression|null $handler = null;
    private ?string $confirmText = null;
    private array $props = [];

    public function __construct(
        private readonly string $label,
        private ActionIntent $intent = ActionIntent::CUSTOM
    ) {
    }

    /**
     * 直接创建一个动作组件实例。
     */
    public static function make(string $label): static
    {
        return new static($label);
    }

    /**
     * 创建“新建”动作，可直接绑定 Dialog 对象或 dialog key。
     */
    public static function create(string|Dialog $labelOrDialog = '新建', string|Dialog|null $dialog = null): DialogAction
    {
        [$label, $target, $dialogDefinition] = self::resolveDialogActionArguments(
            $labelOrDialog,
            $dialog,
            ActionIntent::CREATE
        );

        $action = (new DialogAction($label, ActionIntent::CREATE))
            ->type('primary')
            ->icon('Plus');

        if ($dialogDefinition !== null) {
            return $action->bindDialog($dialogDefinition);
        }

        return $action->target($target);
    }

    /**
     * 创建“编辑”动作，可直接绑定 Dialog 对象或 dialog key。
     */
    public static function edit(string|Dialog $labelOrDialog = '编辑', string|Dialog|null $dialog = null): DialogAction
    {
        [$label, $target, $dialogDefinition] = self::resolveDialogActionArguments(
            $labelOrDialog,
            $dialog,
            ActionIntent::EDIT
        );

        $action = (new DialogAction($label, ActionIntent::EDIT))
            ->type('primary')
            ->icon('Edit');

        if ($dialogDefinition !== null) {
            return $action->bindDialog($dialogDefinition);
        }

        return $action->target($target);
    }

    /**
     * 创建“删除”动作，默认附带删除确认提示。
     */
    public static function delete(string $label = '删除'): self
    {
        return (new self($label, ActionIntent::DELETE))
            ->type('danger')
            ->icon('Delete')
            ->confirm('确认删除当前记录？');
    }

    /**
     * 创建“刷新”动作。
     */
    public static function refresh(string $label = '刷新'): self
    {
        return (new self($label, ActionIntent::REFRESH))
            ->icon('Refresh');
    }

    /**
     * 创建“提交弹窗表单”动作。
     */
    public static function submit(string $label = '保存', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::SUBMIT))
            ->target($dialog)
            ->type('primary');
    }

    /**
     * 创建“关闭弹窗”动作。
     */
    public static function close(string $label = '取消', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::CLOSE))
            ->target($dialog);
    }

    /**
     * 创建一个自定义动作，可绑定 JS 表达式或结构化事件。
     * 若传 JS，handler 使用点击事件上下文，可读取 row / tableKey / listKey / filters /
     * forms / dialogs / selection / vm；动作运行在目标弹窗上下文时还可读取 dialog / dialogKey。
     */
    public static function custom(string $label, string|JsExpression|StructuredEventInterface $handler): self
    {
        return (new self($label, ActionIntent::CUSTOM))->onClick($handler);
    }

    /**
     * 设置按钮类型，例如 primary / success / danger。
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置按钮图标名称。
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * 设置动作自身的稳定 key，便于后续定位或扩展。
     */
    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * 指定动作目标，通常用于绑定 dialog key。
     * 对 submit/close/create/edit 这类依赖弹窗目标的动作，最终会基于这个 target 运行。
     */
    public function target(?string $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * target() 的语义化别名，用于显式绑定 dialog key。
     */
    public function dialog(string $dialog): static
    {
        return $this->target($dialog);
    }

    /**
     * 显式指定当前动作作用到哪个表格。
     * 适合页面头部这类不处于局部表格上下文的位置；否则构建阶段会要求你显式指定目标。
     */
    public function forTable(string|Table $table): static
    {
        $this->tableTarget = $table instanceof Table ? $table->key() : $table;
        $this->listTarget = null;

        return $this;
    }

    /**
     * 显式指定当前动作作用到哪个列表。
     * 适合页面头部或公共布局里的刷新/请求动作，避免依赖模糊的局部上下文。
     */
    public function forList(string|ListWidget $list): static
    {
        $this->listTarget = $list instanceof ListWidget ? $list->key() : $list;
        $this->tableTarget = null;

        return $this;
    }

    /**
     * 绑定点击行为，可传 JS 表达式或结构化事件。
     * 若传 JS 表达式，handler 签名与 on('click', ...) 一致，统一接收一个 context 对象。
     * 常用可读字段：row / tableKey / listKey / filters / forms / dialogs / selection / vm，
     * 以及目标弹窗上下文下的 dialog / dialogKey。
     */
    public function onClick(string|JsExpression|StructuredEventInterface $handler): static
    {
        if ($handler instanceof StructuredEventInterface) {
            return $this->on('click', $handler);
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * 绑定动作事件。
     * 可用事件：click。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ row, tableKey, listKey, filters, forms, dialogs, selection, vm }) => {}`
     * 不按位置参数传值。
     *
     * click 上下文：
     * - action: 当前动作配置
     * - row: 当前行数据；表头动作时通常为 null
     * - tableKey / listKey: 当前动作命中的表格或列表 key
     * - filters / forms / dialogs / selection: 当前页面运行时上下文
     * - dialog / dialogKey: 动作目标指向弹窗且运行时存在对应弹窗时可用
     * - vm: 当前 Vue 实例
     * - reloadTable() / reloadList() / reloadPage() / closeDialog(): 常用运行时辅助方法
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindActionEventHandler($event, $handler);
    }

    /**
     * 设置点击前确认文案；传 null 可取消确认。
     */
    public function confirm(?string $text): static
    {
        $this->confirmText = $text;

        return $this;
    }

    /**
     * 透传额外按钮属性。
     */
    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function intent(): ActionIntent
    {
        return $this->intent;
    }

    public function buttonType(): string
    {
        return $this->type;
    }

    public function iconName(): ?string
    {
        return $this->icon;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function targetName(): ?string
    {
        return $this->target;
    }

    public function handler(): string|JsExpression|null
    {
        return $this->handler;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'click' => '按钮点击时触发。适用于普通动作、自定义动作以及 create/edit/delete/refresh/submit/close 等内置动作。',
        ];
    }

    public function tableTarget(): ?string
    {
        return $this->tableTarget;
    }

    public function listTarget(): ?string
    {
        return $this->listTarget;
    }

    public function confirmText(): ?string
    {
        return $this->confirmText;
    }

    public function attrs(): array
    {
        return $this->props;
    }

    private static function resolveDialogActionArguments(
        string|Dialog $labelOrDialog,
        string|Dialog|null $dialogOrLabel,
        ActionIntent $intent
    ): array {
        $defaultLabel = $intent === ActionIntent::CREATE ? '新建' : '编辑';
        $label = $defaultLabel;
        $target = 'editor';
        $dialog = null;

        if ($labelOrDialog instanceof Dialog) {
            $dialog = $labelOrDialog;
            $target = $dialog->key();
            if (is_string($dialogOrLabel) && $dialogOrLabel !== '') {
                $label = $dialogOrLabel;
            } elseif ($intent === ActionIntent::CREATE) {
                $label = $dialog->title();
            }

            return [$label, $target, $dialog];
        }

        if ($labelOrDialog !== '') {
            $label = $labelOrDialog;
        }

        if ($dialogOrLabel instanceof Dialog) {
            $dialog = $dialogOrLabel;
            $target = $dialog->key();

            return [$label, $target, $dialog];
        }

        if (is_string($dialogOrLabel) && $dialogOrLabel !== '') {
            $target = $dialogOrLabel;
        }

        return [$label, $target, $dialog];
    }
}
