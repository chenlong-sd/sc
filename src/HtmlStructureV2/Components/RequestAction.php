<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Dsl\Events;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class RequestAction extends Action
{
    private const SUPPORTED_ON_EVENTS = ['click', 'before', 'success', 'fail', 'finally'];
    private const PAYLOAD_SOURCE_CUSTOM = 'custom';
    private const PAYLOAD_SOURCE_FORM = 'form';
    private const DEFAULT_MODE_QUERY_KEY = 'id';

    private ?string $requestUrl = null;
    private string $requestMethod = 'post';
    private array|JsExpression $payload = [];
    private string $payloadSource = self::PAYLOAD_SOURCE_CUSTOM;
    private ?string $payloadFormScope = null;
    private bool $validateForm = false;
    private ?string $validateFormScope = null;
    private ?string $successMessage = null;
    private ?string $errorMessage = null;
    private ?string $loadingText = null;
    private bool $reloadTable = false;
    private bool $reloadPage = false;
    private bool $closeAfterSuccess = false;
    private JsExpression|StructuredEventInterface|null $beforeHook = null;
    private JsExpression|StructuredEventInterface|null $afterSuccessHook = null;
    private JsExpression|StructuredEventInterface|null $afterFailHook = null;
    private JsExpression|StructuredEventInterface|null $afterFinallyHook = null;
    private ?string $saveCreateUrl = null;
    private ?string $saveUpdateUrl = null;
    private ?string $saveModeQueryKey = null;

    public function __construct(string $label)
    {
        parent::__construct($label, ActionIntent::REQUEST);
    }

    /**
     * 直接创建一个请求动作实例。
     */
    public static function make(string $label): static
    {
        return new static($label);
    }

    /**
     * 配置请求地址和请求方法。
     * `url` 支持在前端运行时解析上下文 token。
     * 常用可用字段：
     * - action / row / tableKey / listKey
     * - filters / forms / dialogs / selection / query / page / mode
     * - dialog / dialogKey: 当前动作运行在目标弹窗上下文时可用
     * - vm
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData() / setFormModel() / initializeFormModel() / resetForm()
     * 例如可写 "@row.id"、"@dialogKey"、"@filters.keyword"、"@page.query.id"。
     */
    public function request(string $url, string $method = 'post'): static
    {
        $this->requestUrl = $url;
        $this->requestMethod = strtolower($method) ?: 'post';
        $this->saveCreateUrl = null;
        $this->saveUpdateUrl = null;
        $this->saveModeQueryKey = null;

        return $this;
    }

    /**
     * 以 GET 方式发起请求。
     */
    public function get(string $url): static
    {
        return $this->request($url, 'get');
    }

    /**
     * 以 POST 方式发起请求。
     */
    public function post(string $url): static
    {
        return $this->request($url, 'post');
    }

    /**
     * 以 PUT 方式发起请求。
     */
    public function put(string $url): static
    {
        return $this->request($url, 'put');
    }

    /**
     * 以 PATCH 方式发起请求。
     */
    public function patch(string $url): static
    {
        return $this->request($url, 'patch');
    }

    /**
     * 以 DELETE 方式发起请求。
     */
    public function deleteRequest(string $url): static
    {
        return $this->request($url, 'delete');
    }

    /**
     * 设置请求体或动态 payload。
     * 传数组时，内部以 "@" 开头的值会按当前动作 context 解析；
     * 传字符串或 JsExpression 时，会按前端表达式处理，并可直接返回最终请求体对象。
     * 调用该方法会切回“自定义 payload”模式，覆盖之前的 payloadFromForm() 设置。
     *
     * 解析时可用字段：
     * - action / row / tableKey / listKey
     * - filters / forms / dialogs / selection / query / page / mode
     * - dialog / dialogKey: 当前动作运行在弹窗上下文时可用
     * - vm
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     *
     * 常见写法：
     * - "@row.id"
     * - "@filters.keyword"
     * - "@forms.profile.name"
     * - "@page.query.id"
     * - "(ctx) => ctx.cloneFormModel('profile')"
     * - "{ id: row?.id, ids: selection?.map(item => item.id) ?? [] }"
     */
    public function payload(array|string|JsExpression $payload): static
    {
        $this->payload = is_string($payload) ? JsExpression::ensure($payload) : $payload;
        $this->payloadSource = self::PAYLOAD_SOURCE_CUSTOM;
        $this->payloadFormScope = null;

        return $this;
    }

    /**
     * 请求前先校验指定表单。
     * 常用于独立表单页保存；成功时才会继续组装 payload 并发起请求。
     *
     * - 传表单 key 时，会校验该表单
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     * - 自动解析优先当前 dialog 表单，其次页面内唯一的非 dialog 表单
     *
     * 推荐搭配：
     * - `payloadFromForm("profile")`
     * - `submitForm("profile")`
     */
    public function validateForm(?string $scope = null): static
    {
        $this->validateForm = true;
        $this->validateFormScope = $this->normalizeFormScope($scope);

        return $this;
    }

    /**
     * 直接把表单当前模型作为请求 payload。
     * 会输出一个适合请求提交的深拷贝结果，避免把运行时响应式对象直接送进请求层。
     * 调用该方法会覆盖之前的 payload() 设置。
     *
     * - 传表单 key 时，会读取该表单
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     * - 自动解析规则与 validateForm() 相同
     *
     * 常见用法：
     * - `payloadFromForm("profile")`
     * - `validateForm("profile")->payloadFromForm("profile")`
     * - `submitForm("profile")`
     */
    public function payloadFromForm(?string $scope = null): static
    {
        $this->payloadSource = self::PAYLOAD_SOURCE_FORM;
        $this->payloadFormScope = $this->normalizeFormScope($scope);

        return $this;
    }

    /**
     * CRUD 表单保存快捷方式，等价于 validateForm()->payloadFromForm()。
     * 适合独立表单页的“保存”按钮，避免再手写 `ctx.vm.validateSimpleForm(...)` /
     * `ctx.vm.getSimpleFormModel(...)` 这类内部运行时方法名。
     *
     * - 传表单 key 时，会同时用于校验和 payload 读取
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     */
    public function submitForm(?string $scope = null): static
    {
        return $this
            ->validateForm($scope)
            ->payloadFromForm($scope);
    }

    /**
     * CRUD 表单保存地址快捷方式。
     * 会根据当前页面模式自动在 create/update 地址间切换，避免再手写 PHP `if ($isEdit)`。
     *
     * 模式解析规则：
     * - 若当前动作已通过 submitForm()/validateForm()/payloadFromForm() 绑定表单，则优先读取该表单的 modeQueryKey()
     * - 否则会使用这里显式传入的 "$queryKey"
     * - "$updateUrl" 为空时，会回退到 "$createUrl"
     *
     * 常见用法：
     * - `saveUrls('/admin/user/create', '/admin/user/update')`
     * - `saveUrls('/admin/user/save')`
     * - `saveUrls('/admin/user/create', '/admin/user/update', 'user_id')`
     *
     * 调用该方法会切换到“按页面模式选请求地址”模式，覆盖之前固定的 request()/get()/post()/put()/patch()/deleteRequest() 地址。
     * 请求方法仍复用当前动作上的 method 配置，默认值为 post。
     */
    public function saveUrls(
        string $createUrl,
        ?string $updateUrl = null,
        string $queryKey = self::DEFAULT_MODE_QUERY_KEY
    ): static {
        $this->requestUrl = null;
        $this->saveCreateUrl = trim($createUrl);
        $this->saveUpdateUrl = ($updateUrl !== null && trim($updateUrl) !== '') ? trim($updateUrl) : $this->saveCreateUrl;
        $queryKey = trim($queryKey);
        $this->saveModeQueryKey = $queryKey !== '' ? $queryKey : self::DEFAULT_MODE_QUERY_KEY;

        return $this;
    }

    /**
     * 保存成功后执行“优先关闭宿主弹窗，否则跳转到指定 URL”的快捷方式。
     * 等价于 `afterSuccess(Events::returnTo($url)->hostTable($table))`。
     * 常用于独立表单页或 iframe 子表单页的保存成功返回。
     *
     * - 若当前页面由启用 `iframeHost()` 的 V2 iframe 弹窗打开，则会优先关闭宿主弹窗
     * - 若未传 `url` 且当前不在宿主 iframe 弹窗中，则会静默跳过
     * - 若传了 `table`，关闭前会显式请求宿主刷新该表格
     * - 不传 `table` 时，会优先使用当前 success context 的 `tableKey`
     */
    public function returnTo(string|JsExpression|null $url = null, string|Table|null $table = null): static
    {
        return $this->afterSuccess(
            Events::returnTo($url)->hostTable($table)
        );
    }

    /**
     * 设置请求成功后的提示文案。
     */
    public function successMessage(?string $successMessage): static
    {
        $this->successMessage = $successMessage;

        return $this;
    }

    /**
     * 设置请求失败后的提示文案。
     */
    public function errorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * 设置请求进行中的 loading 文案。
     */
    public function loadingText(?string $loadingText = '请稍后...'): static
    {
        $this->loadingText = $loadingText;

        return $this;
    }

    /**
     * 请求成功后刷新当前表格。
     * 若当前动作实际绑定的是列表而未显式指定 tableKey，会优先走列表刷新。
     */
    public function reloadTable(bool $reloadTable = true): static
    {
        $this->reloadTable = $reloadTable;

        return $this;
    }

    /**
     * 请求成功后刷新整页。
     */
    public function reloadPage(bool $reloadPage = true): static
    {
        $this->reloadPage = $reloadPage;

        return $this;
    }

    /**
     * 请求成功后自动关闭当前弹窗。
     * 仅当当前动作存在 dialog target 且运行时处于对应弹窗上下文时生效。
     */
    public function closeAfterSuccess(bool $closeAfterSuccess = true): static
    {
        $this->closeAfterSuccess = $closeAfterSuccess;

        return $this;
    }

    /**
     * 设置请求开始前钩子。
     * handler 签名与 `on('before', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 before 事件一致，常用有 action / row / tableKey / listKey / filters / forms /
     * dialogs / selection / query / page / mode / dialog / dialogKey / vm，以及已组装好的 request。
     * 若当前请求动作使用了 validateForm()/payloadFromForm()/submitForm()，还可读取：
     * - formScope
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     * - closeHostDialog() / reloadHostTable() / openHostDialog()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     */
    public function before(string|JsExpression|StructuredEventInterface $beforeHook): static
    {
        if (is_string($beforeHook)) {
            $beforeHook = JsExpression::ensure($beforeHook);
        }
        $this->beforeHook = $beforeHook;
        $this->on('before', $beforeHook);

        return $this;
    }

    /**
     * 设置请求成功后的钩子。
     * handler 签名与 `on('success', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 success 事件一致，常用有 request / response / payload / row / filters /
     * forms / dialogs / selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() 等宿主桥方法。
     */
    public function afterSuccess(string|JsExpression|StructuredEventInterface $afterSuccessHook): static
    {
        if (is_string($afterSuccessHook)) {
            $afterSuccessHook = JsExpression::ensure($afterSuccessHook);
        }
        $this->afterSuccessHook = $afterSuccessHook;
        $this->on('success', $afterSuccessHook);

        return $this;
    }

    /**
     * 设置请求失败后的钩子。
     * handler 签名与 `on('fail', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 fail 事件一致，常用有 request / error / row / filters / forms / dialogs /
     * selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() 等宿主桥方法。
     */
    public function afterFail(string|JsExpression|StructuredEventInterface $afterFailHook): static
    {
        if (is_string($afterFailHook)) {
            $afterFailHook = JsExpression::ensure($afterFailHook);
        }
        $this->afterFailHook = $afterFailHook;
        $this->on('fail', $afterFailHook);

        return $this;
    }

    /**
     * 设置请求结束后的 finally 钩子。
     * handler 签名与 `on('finally', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 finally 事件一致，常用有 request，以及可能存在的 response / payload / error，
     * 同时也可读取 row / filters / forms / dialogs / selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() 等宿主桥方法。
     */
    public function afterFinally(string|JsExpression|StructuredEventInterface $afterFinallyHook): static
    {
        if (is_string($afterFinallyHook)) {
            $afterFinallyHook = JsExpression::ensure($afterFinallyHook);
        }
        $this->afterFinallyHook = $afterFinallyHook;
        $this->on('finally', $afterFinallyHook);

        return $this;
    }

    /**
     * 绑定请求动作事件。
     * 可用事件：click / before / success / fail / finally。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ request, response, payload, error, row, tableKey, listKey, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - action / row / tableKey / listKey / filters / forms / dialogs / selection / query / page / mode / vm
     * - dialog / dialogKey: 动作目标指向弹窗且运行时存在对应弹窗时可用
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     * - notifyDialogHost() / closeHostDialog() / reloadHostTable() / openHostDialog()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     *
     * 事件额外字段：
     * - click: 无额外字段，常用于点击前拦截
     * - before: request
     * - success: request / response / payload
     * - fail: request / error
     * - finally: request，以及可能存在的 response / payload / error
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return parent::on($event, $handler);
    }

    public function getRequestUrl(): ?string
    {
        return $this->requestUrl;
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function getPayload(): array|JsExpression
    {
        return $this->payload;
    }

    public function getPayloadSource(): string
    {
        return $this->payloadSource;
    }

    public function getPayloadFormScope(): ?string
    {
        return $this->payloadFormScope;
    }

    public function shouldValidateForm(): bool
    {
        return $this->validateForm;
    }

    public function getValidateFormScope(): ?string
    {
        return $this->validateFormScope;
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

    public function shouldReloadTable(): bool
    {
        return $this->reloadTable;
    }

    public function shouldReloadPage(): bool
    {
        return $this->reloadPage;
    }

    public function shouldCloseAfterSuccess(): bool
    {
        return $this->closeAfterSuccess;
    }

    public function getBeforeHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->beforeHook;
    }

    public function getAfterSuccessHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterSuccessHook;
    }

    public function getAfterFailHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterFailHook;
    }

    public function getAfterFinallyHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterFinallyHook;
    }

    public function getSaveCreateUrl(): ?string
    {
        return $this->saveCreateUrl;
    }

    public function getSaveUpdateUrl(): ?string
    {
        return $this->saveUpdateUrl;
    }

    public function getSaveModeQueryKey(): ?string
    {
        return $this->saveModeQueryKey;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'click' => '点击请求按钮时触发，先于真正的请求流程执行；返回 false 可中断后续执行。',
            'before' => '请求发出前触发，适合二次校验、动态补充上下文或主动取消请求。',
            'success' => '请求成功且接口返回成功态后触发，可读取 response / payload。',
            'fail' => '请求失败或接口返回失败态时触发，可读取 error。',
            'finally' => '请求结束后总会触发，无论成功还是失败。',
        ];
    }

    private function normalizeFormScope(?string $scope): ?string
    {
        $scope = is_string($scope) ? trim($scope) : null;

        return $scope !== '' ? $scope : null;
    }
}
