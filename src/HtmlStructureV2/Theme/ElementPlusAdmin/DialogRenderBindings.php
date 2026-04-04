<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class DialogRenderBindings
{
    public function __construct(
        private readonly ?string $titleExpression = null,
        private readonly ?string $iframeUrlExpression = null,
        private readonly ?string $componentPropsExpression = null,
        private readonly ?string $loadingExpression = null,
        private readonly ?string $beforeCloseExpression = null,
        private readonly ?string $closedExpression = null,
        private readonly ?string $fullscreenExpression = null,
        private readonly ?string $iframeLoadExpression = null,
        private readonly ?string $componentRefExpression = null,
        private readonly ?string $iframeRefExpression = null,
        private readonly ?string $toggleFullscreenExpression = null,
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public function title(?string $expression): self
    {
        return new self(
            titleExpression: $expression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function iframeUrl(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $expression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function componentProps(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $expression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function loading(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $expression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function beforeClose(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $expression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function closed(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $expression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function fullscreen(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $expression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function iframeLoad(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $expression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function componentRef(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $expression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function iframeRef(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $expression,
            toggleFullscreenExpression: $this->toggleFullscreenExpression,
        );
    }

    public function toggleFullscreen(?string $expression): self
    {
        return new self(
            titleExpression: $this->titleExpression,
            iframeUrlExpression: $this->iframeUrlExpression,
            componentPropsExpression: $this->componentPropsExpression,
            loadingExpression: $this->loadingExpression,
            beforeCloseExpression: $this->beforeCloseExpression,
            closedExpression: $this->closedExpression,
            fullscreenExpression: $this->fullscreenExpression,
            iframeLoadExpression: $this->iframeLoadExpression,
            componentRefExpression: $this->componentRefExpression,
            iframeRefExpression: $this->iframeRefExpression,
            toggleFullscreenExpression: $expression,
        );
    }

    public function titleExpression(): ?string
    {
        return $this->titleExpression;
    }

    public function iframeUrlExpression(): ?string
    {
        return $this->iframeUrlExpression;
    }

    public function componentPropsExpression(): ?string
    {
        return $this->componentPropsExpression;
    }

    public function loadingExpression(): ?string
    {
        return $this->loadingExpression;
    }

    public function beforeCloseExpression(): ?string
    {
        return $this->beforeCloseExpression;
    }

    public function closedExpression(): ?string
    {
        return $this->closedExpression;
    }

    public function fullscreenExpression(): ?string
    {
        return $this->fullscreenExpression;
    }

    public function iframeLoadExpression(): ?string
    {
        return $this->iframeLoadExpression;
    }

    public function componentRefExpression(): ?string
    {
        return $this->componentRefExpression;
    }

    public function iframeRefExpression(): ?string
    {
        return $this->iframeRefExpression;
    }

    public function toggleFullscreenExpression(): ?string
    {
        return $this->toggleFullscreenExpression;
    }
}
