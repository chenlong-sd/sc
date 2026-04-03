<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Dialog implements Renderable
{
    use RendersWithTheme;

    private string $width = '760px';
    private ?string $height = null;
    private bool $draggable = false;
    private bool $fullscreen = false;
    private bool $alignCenter = false;
    private bool $closeOnClickModal = false;
    private bool $destroyOnClose = true;
    private ?string $saveUrl = null;
    private ?string $createUrl = null;
    private ?string $updateUrl = null;
    private ?string $titleTemplate = null;
    private array $footerActions = [];
    private ?Form $form = null;
    private string|AbstractHtmlElement|null $content = null;
    private array|JsExpression|null $iframeQuery = null;
    private ?string $iframeUrl = null;
    private ?string $loadUrl = null;
    private string $loadMethod = 'get';
    private array|JsExpression $loadPayload = [];
    private ?string $loadDataPath = null;
    private string $loadWhen = 'edit';
    private ?JsExpression $beforeOpenHook = null;
    private ?JsExpression $afterOpenHook = null;
    private ?JsExpression $beforeCloseHook = null;
    private ?JsExpression $afterCloseHook = null;
    private array $props = [];

    public function __construct(
        private readonly string $key,
        private readonly string $title
    ) {
    }

    public static function make(string $key, string $title): self
    {
        return new self($key, $title);
    }

    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function height(?string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function draggable(bool $draggable = true): self
    {
        $this->draggable = $draggable;

        return $this;
    }

    public function fullscreen(bool $fullscreen = true): self
    {
        $this->fullscreen = $fullscreen;

        return $this;
    }

    public function alignCenter(bool $alignCenter = true): self
    {
        $this->alignCenter = $alignCenter;

        return $this;
    }

    public function closeOnClickModal(bool $closeOnClickModal = true): self
    {
        $this->closeOnClickModal = $closeOnClickModal;

        return $this;
    }

    public function destroyOnClose(bool $destroyOnClose = true): self
    {
        $this->destroyOnClose = $destroyOnClose;

        return $this;
    }

    public function saveUrl(?string $saveUrl): self
    {
        $this->saveUrl = $saveUrl;

        return $this;
    }

    public function createUrl(?string $createUrl): self
    {
        $this->createUrl = $createUrl;

        return $this;
    }

    public function updateUrl(?string $updateUrl): self
    {
        $this->updateUrl = $updateUrl;

        return $this;
    }

    public function titleTemplate(?string $titleTemplate): self
    {
        $this->titleTemplate = $titleTemplate;

        return $this;
    }

    public function form(Form $form): self
    {
        $this->form = $form;
        $this->content = null;
        $this->iframeUrl = null;
        $this->iframeQuery = null;

        return $this;
    }

    public function content(string|AbstractHtmlElement|null $content): self
    {
        $this->content = $content;
        $this->form = null;
        $this->iframeUrl = null;
        $this->iframeQuery = null;

        return $this;
    }

    public function iframe(string $url, array|JsExpression $query = []): self
    {
        $this->iframeUrl = $url;
        $this->iframeQuery = $query;
        $this->form = null;
        $this->content = null;

        return $this;
    }

    public function load(string $url, string $method = 'get'): self
    {
        $this->loadUrl = $url;
        $this->loadMethod = strtolower($method) ?: 'get';

        return $this;
    }

    public function loadPayload(array|JsExpression $loadPayload): self
    {
        $this->loadPayload = $loadPayload;

        return $this;
    }

    public function loadDataPath(?string $loadDataPath): self
    {
        $this->loadDataPath = $loadDataPath;

        return $this;
    }

    public function loadWhen(string $loadWhen): self
    {
        $loadWhen = strtolower($loadWhen);
        if (in_array($loadWhen, ['always', 'create', 'edit'], true)) {
            $this->loadWhen = $loadWhen;
        }

        return $this;
    }

    public function beforeOpen(JsExpression $beforeOpenHook): self
    {
        $this->beforeOpenHook = $beforeOpenHook;

        return $this;
    }

    public function afterOpen(JsExpression $afterOpenHook): self
    {
        $this->afterOpenHook = $afterOpenHook;

        return $this;
    }

    public function beforeClose(JsExpression $beforeCloseHook): self
    {
        $this->beforeCloseHook = $beforeCloseHook;

        return $this;
    }

    public function afterClose(JsExpression $afterCloseHook): self
    {
        $this->afterCloseHook = $afterCloseHook;

        return $this;
    }

    public function props(array $props): self
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    public function footer(Action ...$actions): self
    {
        $this->footerActions = array_merge($this->footerActions, $actions);

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function isDraggable(): bool
    {
        return $this->draggable;
    }

    public function isFullscreen(): bool
    {
        return $this->fullscreen;
    }

    public function isAlignCenter(): bool
    {
        return $this->alignCenter;
    }

    public function shouldCloseOnClickModal(): bool
    {
        return $this->closeOnClickModal;
    }

    public function shouldDestroyOnClose(): bool
    {
        return $this->destroyOnClose;
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

    public function getTitleTemplate(): ?string
    {
        return $this->titleTemplate;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function getContent(): string|AbstractHtmlElement|null
    {
        return $this->content;
    }

    public function getIframeUrl(): ?string
    {
        return $this->iframeUrl;
    }

    public function getIframeQuery(): array|JsExpression|null
    {
        return $this->iframeQuery;
    }

    public function getLoadUrl(): ?string
    {
        return $this->loadUrl;
    }

    public function getLoadMethod(): string
    {
        return $this->loadMethod;
    }

    public function getLoadPayload(): array|JsExpression
    {
        return $this->loadPayload;
    }

    public function getLoadDataPath(): ?string
    {
        return $this->loadDataPath;
    }

    public function getLoadWhen(): string
    {
        return $this->loadWhen;
    }

    public function getBeforeOpenHook(): ?JsExpression
    {
        return $this->beforeOpenHook;
    }

    public function getAfterOpenHook(): ?JsExpression
    {
        return $this->afterOpenHook;
    }

    public function getBeforeCloseHook(): ?JsExpression
    {
        return $this->beforeCloseHook;
    }

    public function getAfterCloseHook(): ?JsExpression
    {
        return $this->afterCloseHook;
    }

    public function getFooterActions(): array
    {
        return $this->footerActions;
    }

    public function attrs(): array
    {
        return $this->props;
    }

    public function bodyType(): string
    {
        if ($this->iframeUrl !== null && $this->iframeUrl !== '') {
            return 'iframe';
        }

        if ($this->form !== null) {
            return 'form';
        }

        if ($this->content !== null) {
            return 'content';
        }

        return 'empty';
    }
}
