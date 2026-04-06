<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class RequestAction extends Action
{
    private const SUPPORTED_ON_EVENTS = ['click', 'before', 'success', 'fail', 'finally'];

    private ?string $requestUrl = null;
    private string $requestMethod = 'post';
    private array|JsExpression $payload = [];
    private ?string $successMessage = null;
    private ?string $errorMessage = null;
    private ?string $loadingText = null;
    private bool $reloadTable = false;
    private bool $reloadPage = false;
    private bool $closeAfterSuccess = false;
    private ?JsExpression $beforeHook = null;
    private ?JsExpression $afterSuccessHook = null;
    private ?JsExpression $afterFailHook = null;
    private ?JsExpression $afterFinallyHook = null;

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
     * - filters / forms / dialogs / selection
     * - dialog / dialogKey: 当前动作运行在目标弹窗上下文时可用
     * - vm
     * 例如可写 "@row.id"、"@dialogKey"、"@filters.keyword"。
     */
    public function request(string $url, string $method = 'post'): static
    {
        $this->requestUrl = $url;
        $this->requestMethod = strtolower($method) ?: 'post';

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
     *
     * 解析时可用字段：
     * - action / row / tableKey / listKey
     * - filters / forms / dialogs / selection
     * - dialog / dialogKey: 当前动作运行在弹窗上下文时可用
     * - vm
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
     *
     * 常见写法：
     * - "@row.id"
     * - "@filters.keyword"
     * - "@forms.profile.name"
     * - "{ id: row?.id, ids: selection?.map(item => item.id) ?? [] }"
     */
    public function payload(array|string|JsExpression $payload): static
    {
        $this->payload = is_string($payload) ? JsExpression::ensure($payload) : $payload;

        return $this;
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
     * 传字符串时会自动包装成 JsExpression。
     * 可用字段与 before 事件一致，常用有 action / row / tableKey / listKey / filters / forms /
     * dialogs / selection / dialog / dialogKey / vm，以及已组装好的 request。
     */
    public function before(string|JsExpression $beforeHook): static
    {
        $beforeHook = JsExpression::ensure($beforeHook);
        $this->beforeHook = $beforeHook;
        $this->on('before', $beforeHook);

        return $this;
    }

    /**
     * 设置请求成功后的钩子。
     * handler 签名与 `on('success', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 可用字段与 success 事件一致，常用有 request / response / payload / row / filters /
     * forms / dialogs / selection / dialog / dialogKey / vm。
     */
    public function afterSuccess(string|JsExpression $afterSuccessHook): static
    {
        $afterSuccessHook = JsExpression::ensure($afterSuccessHook);
        $this->afterSuccessHook = $afterSuccessHook;
        $this->on('success', $afterSuccessHook);

        return $this;
    }

    /**
     * 设置请求失败后的钩子。
     * handler 签名与 `on('fail', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 可用字段与 fail 事件一致，常用有 request / error / row / filters / forms / dialogs /
     * selection / dialog / dialogKey / vm。
     */
    public function afterFail(string|JsExpression $afterFailHook): static
    {
        $afterFailHook = JsExpression::ensure($afterFailHook);
        $this->afterFailHook = $afterFailHook;
        $this->on('fail', $afterFailHook);

        return $this;
    }

    /**
     * 设置请求结束后的 finally 钩子。
     * handler 签名与 `on('finally', ...)` 一致，只接收一个 context 对象。
     * 传字符串时会自动包装成 JsExpression。
     * 可用字段与 finally 事件一致，常用有 request，以及可能存在的 response / payload / error，
     * 同时也可读取 row / filters / forms / dialogs / selection / dialog / dialogKey / vm。
     */
    public function afterFinally(string|JsExpression $afterFinallyHook): static
    {
        $afterFinallyHook = JsExpression::ensure($afterFinallyHook);
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
     * - action / row / tableKey / listKey / filters / forms / dialogs / selection / vm
     * - dialog / dialogKey: 动作目标指向弹窗且运行时存在对应弹窗时可用
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
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

    public function getBeforeHook(): ?JsExpression
    {
        return $this->beforeHook;
    }

    public function getAfterSuccessHook(): ?JsExpression
    {
        return $this->afterSuccessHook;
    }

    public function getAfterFailHook(): ?JsExpression
    {
        return $this->afterFailHook;
    }

    public function getAfterFinallyHook(): ?JsExpression
    {
        return $this->afterFinallyHook;
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
}
