<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Dsl\Events;
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

    private string $type = 'primary';
    private ?string $icon = null;
    private ?string $key = null;
    private ?string $target = null;
    private ?string $tableTarget = null;
    private ?string $listTarget = null;
    private string|JsExpression|null $handler = null;
    private ?string $confirmText = null;
    private ?string $saveUrl = null;
    private ?string $createUrl = null;
    private ?string $updateUrl = null;
    private ?string $deleteUrl = null;
    private ?string $deleteKey = null;
    private array $props = [];
    private bool $available = true;

    public function __construct(
        private readonly string $label,
        private ActionIntent $intent = ActionIntent::CUSTOM
    ) {
    }

    /**
     * 直接创建一个动作组件实例。
     * 默认按钮类型为 primary，适合页头、工具栏这类主操作入口。
     */
    public static function make(string $label): static
    {
        return new static($label);
    }

    /**
     * 创建“新建”动作。
     * 额外目标（例如 dialog）请继续链式调用 dialog()/bindDialog()。
     */
    public static function create(string $label = '新建'): DialogAction
    {
        return (new DialogAction($label, ActionIntent::CREATE))
            ->type('primary')
            ->icon('Plus');
    }

    /**
     * 创建“编辑”动作。
     * 额外目标（例如 dialog）请继续链式调用 dialog()/bindDialog()。
     */
    public static function edit(string $label = '编辑'): DialogAction
    {
        return (new DialogAction($label, ActionIntent::EDIT))
            ->type('primary')
            ->icon('Edit');
    }

    /**
     * 创建“删除”动作，语义上用于表格/列表工具栏里的批量删除快捷。
     * 默认会基于当前 selection 走删除接口；不用于 rowActions() 单条删除。
     * 如需就近覆盖删除接口或主键字段，可继续链式调用 deleteUrl()/deleteKey()。
     */
    public static function delete(string $label = '删除'): self
    {
        return (new self($label, ActionIntent::DELETE))
            ->type('danger')
            ->icon('Delete')
            ->confirm('确认删除当前选中数据？');
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
     * 创建“提交弹窗数据”动作。
     * 目标为 form 弹窗时会直接提交表单；
     * 目标为 iframe 弹窗时，会先调用子页面的提交方法取数据，再按 dialog 的 saveUrl()/createUrl()/updateUrl() 提交。
     * 如需就近覆盖提交地址，可继续链式调用 saveUrl()/createUrl()/updateUrl()。
     */
    public static function submit(string $label = '保存'): self
    {
        return (new self($label, ActionIntent::SUBMIT))
            ->type('primary');
    }

    /**
     * 创建“关闭弹窗”动作。
     */
    public static function close(string $label = '取消'): self
    {
        return (new self($label, ActionIntent::CLOSE))
            ->type('default');
    }

    /**
     * 创建“重置表单”动作。
     * 默认会把目标表单恢复到初始值快照；若当前运行时只有一个可解析表单，可省略 "$scope"。
     * 这份快照通常来自表单首次渲染值、`Form::setData()`、独立表单页 `load()` 成功结果，
     * 以及 form 弹窗最近一次打开并初始化/加载后的结果。
     */
    public static function resetForm(string $label = '重置', ?string $scope = null): self
    {
        return self::custom($label, Events::resetForm($scope))
            ->type('default')
            ->icon('RefreshLeft');
    }

    /**
     * 创建一个自定义动作，可绑定 JS 表达式或结构化事件。
     * 若传 JS，handler 使用点击事件上下文，可读取 row / tableKey / listKey / filters /
     * forms / dialogs / selection / vm；若动作渲染在 dialog footer 等弹窗上下文中，
     * 还可直接读取当前 dialog 的 dialog / dialogKey / row / tableKey。
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
     * 其中 submit 若命中 iframe 弹窗，还会继续读取该 dialog 配置的 iframe 提交入口。
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
     * 若动作运行在 dialog footer 等弹窗上下文中，还可读取当前 dialog 的 dialog / dialogKey。
     * 运行在 iframe 子页面时，还可调用：
     * - closeHostDialog() / reloadHostTable() / openHostDialog()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
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
     * 控制当前动作是否在 PHP 层生效。
     *
     * - `->when($condition)`：条件不成立时，动作会被整条渲染/校验/收集链忽略
     * - `->when($condition, fn (Action $action) => ...)`：沿用 Conditionable 语义做链式配置
     */
    public function when(bool $condition, ?callable $callback = null, ?callable $otherwise = null): static
    {
        if ($callback !== null || $otherwise !== null) {
            if ($condition) {
                $callback?->__invoke($this);

                return $this;
            }

            $otherwise?->__invoke($this);

            return $this;
        }

        return $this->available($condition);
    }

    /**
     * 显式设置当前动作是否可用。
     * 不可用时不会参与渲染、target 校验、事件 target 校验、弹窗收集。
     */
    public function available(bool $available = true): static
    {
        $this->available = $available;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
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
     * - row: 当前行数据；表头动作时通常为 null；dialog footer 动作时为当前 dialog row
     * - tableKey / listKey: 当前动作命中的表格或列表 key
     * - filters / forms / dialogs / selection: 当前页面运行时上下文
     * - dialog / dialogKey: 当前 dialog 上下文存在时可用；动作显式指向目标弹窗时也会补齐
     * - vm: 当前 Vue 实例
     * - reloadTable() / reloadList() / reloadPage() / closeDialog() / openDialog(): 常用运行时辅助方法
     * - notifyDialogHost() / closeHostDialog() / reloadHostTable() / openHostDialog(): iframe 子页面常用宿主桥接方法
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
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
     * 为 `Actions::submit()` 就近设置统一提交地址。
     * 优先级高于目标 dialog 上的 saveUrl()/createUrl()/updateUrl()；
     * 适合当前按钮想临时覆盖默认提交地址时使用。
     */
    public function saveUrl(?string $saveUrl): static
    {
        $this->saveUrl = $this->normalizeNullableString($saveUrl);

        return $this;
    }

    /**
     * 为 `Actions::submit()` 就近设置 create 模式提交地址。
     * 优先级高于目标 dialog 上的 createUrl()/saveUrl()。
     */
    public function createUrl(?string $createUrl): static
    {
        $this->createUrl = $this->normalizeNullableString($createUrl);

        return $this;
    }

    /**
     * 为 `Actions::submit()` 就近设置 edit 模式提交地址。
     * 优先级高于目标 dialog 上的 updateUrl()/saveUrl()。
     */
    public function updateUrl(?string $updateUrl): static
    {
        $this->updateUrl = $this->normalizeNullableString($updateUrl);

        return $this;
    }

    /**
     * 为 `Actions::delete()` 就近设置删除接口地址。
     * 优先级高于目标 table/list 默认的 deleteUrl()。
     */
    public function deleteUrl(?string $deleteUrl): static
    {
        $this->deleteUrl = $this->normalizeNullableString($deleteUrl);

        return $this;
    }

    /**
     * 为 `Actions::delete()` 就近设置从 selection 中提取主键时使用的字段名。
     * 优先级高于目标 table/list 默认的 deleteKey()。
     */
    public function deleteKey(?string $deleteKey): static
    {
        $this->deleteKey = $this->normalizeNullableString($deleteKey);

        return $this;
    }

    /**
     * 透传额外按钮属性。
     * 若键名以 ":" 开头，数组/布尔/数字/null 会自动转成 JS 字面量；
     * 字符串值仍按原始前端表达式输出。
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

    public function getSaveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function getCreateUrl(): ?string
    {
        return $this->createUrl;
    }

    public function getUpdateUrl(): ?string
    {
        return $this->updateUrl;
    }

    public function getDeleteUrl(): ?string
    {
        return $this->deleteUrl;
    }

    public function getDeleteKey(): ?string
    {
        return $this->deleteKey;
    }

    public function attrs(): array
    {
        return $this->props;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

}
