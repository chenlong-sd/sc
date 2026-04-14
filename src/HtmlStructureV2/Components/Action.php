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

class Action implements
    Renderable,
    EventAware
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
    private ?string $successMessage = null;
    private ?string $errorMessage = null;
    private ?string $loadingText = null;
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
     *
     * @param string $label 按钮显示文案。
     * @return static 动作实例。
     *
     * 示例：
     * `Action::make('导出')->icon('Download')->type('success')`
     */
    public static function make(string $label): static
    {
        return new static($label);
    }

    /**
     * 创建“新建”动作。
     * 额外目标（例如 dialog）请继续链式调用 dialog()/bindDialog()。
     *
     * @param string $label 按钮显示文案，默认值为“新建”。
     * @return DialogAction 返回支持绑定弹窗的动作实例。
     *
     * 示例：
     * `Action::create('新增')->dialog('qa-info-dialog')`
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
     *
     * @param string $label 按钮显示文案，默认值为“编辑”。
     * @return DialogAction 返回支持绑定弹窗的动作实例。
     *
     * 示例：
     * `Action::edit()->dialog('qa-info-dialog')`
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
     *
     * @param string $label 按钮显示文案，默认值为“删除”。
     * @return self 删除动作实例。
     *
     * 示例：
     * `Action::delete()->deleteUrl('/admin/qa-info/delete')->deleteKey('id')`
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
     *
     * @param string $label 按钮显示文案，默认值为“刷新”。
     * @return self 刷新动作实例。
     *
     * 示例：
     * `Action::refresh()->forTable('qa-info-table')`
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
     * 若动作放在 dialog footer 中，会默认使用当前 dialog；
     * 其它位置如需显式指定目标 dialog，再链式调用 dialog()。
     * 如需就近覆盖提交地址，可继续链式调用 saveUrl()/createUrl()/updateUrl()；
     * 也可继续链式调用 successMessage()/errorMessage()/loadingText() 覆盖默认提示。
     *
     * @param string $label 按钮显示文案，默认值为“保存”。
     * @return self 弹窗提交动作实例。
     *
     * 示例：
     * `Action::submit()->dialog('qa-info-dialog')->saveUrl('/admin/qa-info/save')`
     */
    public static function submit(string $label = '保存'): self
    {
        return (new self($label, ActionIntent::SUBMIT))
            ->type('primary');
    }

    /**
     * 创建“关闭弹窗”动作。
     * 若动作放在 dialog footer 中，会默认关闭当前 dialog；
     * 其它位置如需显式指定目标 dialog，再链式调用 dialog()。
     *
     * @param string $label 按钮显示文案，默认值为“取消”。
     * @return self 关闭动作实例。
     *
     * 示例：
     * `Action::close()->dialog('qa-info-dialog')`
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
     *
     * @param string $label 按钮显示文案，默认值为“重置”。
     * @param string|null $scope 目标表单 scope；不传时仅在运行时能唯一定位表单时自动解析。
     * @return self 重置表单动作实例。
     *
     * 示例：
     * `Action::resetForm('恢复初始值', 'qa-info-form')`
     */
    public static function resetForm(string $label = '重置', ?string $scope = null): self
    {
        return self::custom($label)
            ->onClick(Events::resetForm($scope))
            ->type('default')
            ->icon('RefreshLeft');
    }

    /**
     * 创建一个自定义动作。
     * 点击逻辑请继续链式调用 onClick() / on('click', ...) 配置，
     * 这样使用侧在 IDE 中能更直观看到“先创建动作，再配置点击行为”。
     *
     * @param string $label 按钮显示文案。
     * @return self 自定义动作实例。
     *
     * 示例：
     * `Action::custom('查看')->onClick('({ row, vm }) => vm.openDetail?.(row)')`
     */
    public static function custom(string $label): self
    {
        return new self($label, ActionIntent::CUSTOM);
    }

    /**
     * 设置按钮类型，例如 primary / success / danger。
     *
     * @param string $type 按钮类型，常用值如 primary、default、success、warning、danger。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('保存草稿')->type('warning')`
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置按钮图标名称。
     *
     * @param string $icon Element Plus 图标名，例如 Check、Edit、Delete。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('保存')->icon('Check')`
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * 设置动作自身的稳定 key，便于后续定位或扩展。
     *
     * @param string $key 动作唯一标识。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('导出')->key('export-action')`
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
     * 若当前动作已经处于 dialog footer，上述 submit/close 默认可省略 target。
     *
     * @param string|null $target 目标弹窗 key；传 null 表示清空目标。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->target('qa-info-dialog')`
     */
    public function target(?string $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * target() 的语义化别名，用于显式绑定 dialog key。
     * 若动作已经位于 dialog footer，submit/close 通常可省略该调用。
     *
     * @param string $dialog 目标弹窗 key。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::close()->dialog('qa-info-dialog')`
     */
    public function dialog(string $dialog): static
    {
        return $this->target($dialog);
    }

    /**
     * 显式指定当前动作作用到哪个表格。
     * 适合页面头部这类不处于局部表格上下文的位置；否则构建阶段会要求你显式指定目标。
     *
     * @param string|Table $table 表格 key 或 Table 对象。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::refresh()->forTable('qa-info-table')`
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
     *
     * @param string|ListWidget $list 列表 key 或 ListWidget 对象。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::refresh()->forList('qa-info-list')`
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
     * - closeHostDialog() / reloadHostTable() / openHostDialog() / openHostTab()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     *
     * @param string|JsExpression|StructuredEventInterface $handler 点击处理逻辑。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('预览')->onClick('({ row, vm }) => vm.openPreview?.(row)')`
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
     *
     * @param bool $condition 是否生效。
     * @param callable|null $callback 条件成立时执行的链式配置回调，签名形如 `fn (Action $action) => ...`。
     * @param callable|null $otherwise 条件不成立时执行的链式配置回调。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('审核')->when($canAudit, fn (Action $action) => $action->type('success'))`
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
     *
     * @param bool $available 是否启用该动作，默认值为 true。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('删除')->available($canDelete)`
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
     * - notifyDialogHost() / closeHostDialog() / reloadHostTable() / openHostDialog() / openHostTab(): iframe 子页面常用宿主桥接方法
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     *
     * @param string $event 事件名，目前仅支持 click。
     * @param string|JsExpression|StructuredEventInterface $handler 事件处理逻辑。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('详情')->on('click', '({ row, vm }) => vm.openDetail?.(row)')`
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
     *
     * @param string|null $text 确认提示文案；传 null 表示取消确认。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::delete()->confirm('确认删除选中记录？')`
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
     *
     * @param string|null $saveUrl 提交地址；传 null 表示取消就近覆盖。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->dialog('qa-info-dialog')->saveUrl('/admin/qa-info/save')`
     */
    public function saveUrl(?string $saveUrl): static
    {
        $this->saveUrl = $this->normalizeNullableString($saveUrl);

        return $this;
    }

    /**
     * 为 `Actions::submit()` 就近设置 create 模式提交地址。
     * 优先级高于目标 dialog 上的 createUrl()/saveUrl()。
     *
     * @param string|null $createUrl 新建模式提交地址；传 null 表示取消就近覆盖。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->createUrl('/admin/qa-info/create')`
     */
    public function createUrl(?string $createUrl): static
    {
        $this->createUrl = $this->normalizeNullableString($createUrl);

        return $this;
    }

    /**
     * 为 `Actions::submit()` 就近设置 edit 模式提交地址。
     * 优先级高于目标 dialog 上的 updateUrl()/saveUrl()。
     *
     * @param string|null $updateUrl 编辑模式提交地址；传 null 表示取消就近覆盖。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->updateUrl('/admin/qa-info/update')`
     */
    public function updateUrl(?string $updateUrl): static
    {
        $this->updateUrl = $this->normalizeNullableString($updateUrl);

        return $this;
    }

    /**
     * 设置当前动作成功后的提示文案。
     * 适用于会发请求的动作，例如 `Actions::submit()`、`Actions::request()`、`Actions::save()`。
     *
     * @param string|null $successMessage 成功提示文案；传 null 表示沿用默认成功提示。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->successMessage('保存成功')`
     */
    public function successMessage(?string $successMessage): static
    {
        $this->successMessage = $this->normalizeNullableString($successMessage);

        return $this;
    }

    /**
     * 设置当前动作失败后的提示文案。
     * 适用于会发请求的动作，例如 `Actions::submit()`、`Actions::request()`、`Actions::save()`。
     *
     * @param string|null $errorMessage 失败提示文案；传 null 表示沿用默认失败提示。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->errorMessage('保存失败，请重试')`
     */
    public function errorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $this->normalizeNullableString($errorMessage);

        return $this;
    }

    /**
     * 设置当前动作进行中的 loading 文案。
     * 适用于会发请求的动作，例如 `Actions::submit()`、`Actions::request()`、`Actions::save()`。
     *
     * @param string|null $loadingText loading 提示文案；传 null 表示沿用默认文案。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::submit()->loadingText('正在提交，请稍后...')`
     */
    public function loadingText(?string $loadingText = '请稍后...'): static
    {
        $this->loadingText = $this->normalizeNullableString($loadingText);

        return $this;
    }

    /**
     * 为 `Actions::delete()` 就近设置删除接口地址。
     * 优先级高于目标 table/list 默认的 deleteUrl()。
     *
     * @param string|null $deleteUrl 删除接口地址；传 null 表示取消就近覆盖。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::delete()->deleteUrl('/admin/qa-info/delete')`
     */
    public function deleteUrl(?string $deleteUrl): static
    {
        $this->deleteUrl = $this->normalizeNullableString($deleteUrl);

        return $this;
    }

    /**
     * 为 `Actions::delete()` 就近设置从 selection 中提取主键时使用的字段名。
     * 优先级高于目标 table/list 默认的 deleteKey()。
     *
     * @param string|null $deleteKey 主键字段名；传 null 表示取消就近覆盖。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::delete()->deleteKey('qa_id')`
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
     *
     * @param array $props 要合并到按钮上的额外属性。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::make('打开')->props(['plain' => true, ':disabled' => 'selection.length === 0'])`
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

    public function getSuccessMessage(): ?string
    {
        return $this->successMessage;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getLoadingText(): ?string
    {
        return $this->loadingText;
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
