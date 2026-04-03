<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class RequestAction extends Action
{
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

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function request(string $url, string $method = 'post'): static
    {
        $this->requestUrl = $url;
        $this->requestMethod = strtolower($method) ?: 'post';

        return $this;
    }

    public function get(string $url): static
    {
        return $this->request($url, 'get');
    }

    public function post(string $url): static
    {
        return $this->request($url, 'post');
    }

    public function put(string $url): static
    {
        return $this->request($url, 'put');
    }

    public function patch(string $url): static
    {
        return $this->request($url, 'patch');
    }

    public function deleteRequest(string $url): static
    {
        return $this->request($url, 'delete');
    }

    public function payload(array|JsExpression $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function successMessage(?string $successMessage): static
    {
        $this->successMessage = $successMessage;

        return $this;
    }

    public function errorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function loadingText(?string $loadingText = '请稍后...'): static
    {
        $this->loadingText = $loadingText;

        return $this;
    }

    public function reloadTable(bool $reloadTable = true): static
    {
        $this->reloadTable = $reloadTable;

        return $this;
    }

    public function reloadPage(bool $reloadPage = true): static
    {
        $this->reloadPage = $reloadPage;

        return $this;
    }

    public function closeAfterSuccess(bool $closeAfterSuccess = true): static
    {
        $this->closeAfterSuccess = $closeAfterSuccess;

        return $this;
    }

    public function before(JsExpression $beforeHook): static
    {
        $this->beforeHook = $beforeHook;

        return $this;
    }

    public function afterSuccess(JsExpression $afterSuccessHook): static
    {
        $this->afterSuccessHook = $afterSuccessHook;

        return $this;
    }

    public function afterFail(JsExpression $afterFailHook): static
    {
        $this->afterFailHook = $afterFailHook;

        return $this;
    }

    public function afterFinally(JsExpression $afterFinallyHook): static
    {
        $this->afterFinallyHook = $afterFinallyHook;

        return $this;
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
}
