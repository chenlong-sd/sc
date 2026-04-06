<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\RenderContext;

final class PageFrameRenderer
{
    public function __construct(
        private readonly ActionButtonRenderer $actionButtonRenderer,
        private readonly LightweightComponentRenderer $lightweightComponentRenderer,
    ) {
    }

    /**
     * @param AbstractHtmlElement[] $sections
     */
    public function render(
        AbstractPage $page,
        array $sections,
        ?AbstractHtmlElement $primaryContent = null,
        ?TableRenderBindings $tableBindings = null,
        ?RenderContext $renderContext = null
    ): DoubleLabel {
        $body = El::double('div')->addClass('sc-v2-page');
        $header = $this->renderHeader($page, $tableBindings, $renderContext);
        if ($header !== null) {
            $body->append($header);
        }

        if ($primaryContent !== null) {
            $body->append($primaryContent);
        }

        foreach ($sections as $section) {
            $body->append($section);
        }

        return $body;
    }

    private function renderHeader(
        AbstractPage $page,
        ?TableRenderBindings $tableBindings = null,
        ?RenderContext $renderContext = null
    ): ?AbstractHtmlElement
    {
        $hasCustomHeader = $page->getHeaderContent() !== [];
        $hasActions = $page->getHeaderActions() !== [];

        if (!$hasCustomHeader && !$hasActions) {
            return null;
        }

        $header = El::double('div')->addClass('sc-v2-page__header');
        if ($hasCustomHeader) {
            $header->append($this->renderCustomHeaderContent($page, $renderContext));
        }

        if ($page->getHeaderActions()) {
            $actions = El::double('div')->addClass('sc-v2-actions');

            foreach ($page->getHeaderActions() as $action) {
                $actions->append($this->actionButtonRenderer->render($action, false, 'default', $tableBindings, $renderContext, 'page-header'));
            }

            $header->append($actions);
        }

        return $header;
    }

    private function renderCustomHeaderContent(AbstractPage $page, ?RenderContext $renderContext = null): AbstractHtmlElement
    {
        $header = El::double('div')->addClass('sc-v2-page__title');

        foreach ($page->getHeaderContent() as $content) {
            if (is_string($content) || $content instanceof AbstractHtmlElement) {
                $header->append($content);
                continue;
            }

            if (!$content instanceof Renderable) {
                throw new InvalidArgumentException('Unsupported V2 page header content: ' . get_debug_type($content));
            }

            if ($renderContext === null) {
                throw new InvalidArgumentException('V2 page header render requires render context for renderable content.');
            }

            if (!$this->lightweightComponentRenderer->supportsTree($content)) {
                throw new InvalidArgumentException('Unsupported V2 page header component: ' . $content::class);
            }

            $header->append($this->lightweightComponentRenderer->render($content, $renderContext));
        }

        return $header;
    }
}
