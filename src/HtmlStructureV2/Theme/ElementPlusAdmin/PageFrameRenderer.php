<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\RenderContext;

final class PageFrameRenderer
{
    public function __construct(
        private readonly ActionButtonRenderer $actionButtonRenderer,
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
        $body->append($this->renderHeader($page, $tableBindings, $renderContext));

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
    ): AbstractHtmlElement
    {
        $header = El::double('div')->addClass('sc-v2-page__header');
        $title = El::double('div')->addClass('sc-v2-page__title')
            ->append(El::double('h1')->append($page->title()));

        if ($page->getDescription()) {
            $title->append(El::double('p')->append($page->getDescription()));
        }

        $header->append($title);

        if ($page->getHeaderActions()) {
            $actions = El::double('div')->addClass('sc-v2-actions');

            foreach ($page->getHeaderActions() as $action) {
                $actions->append($this->actionButtonRenderer->render($action, false, 'default', $tableBindings, $renderContext));
            }

            $header->append($actions);
        }

        return $header;
    }
}
