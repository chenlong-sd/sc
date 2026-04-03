<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\RenderContext;

final class DialogRenderer
{
    public function __construct(
        private readonly FormRenderer $formRenderer,
        private readonly ActionButtonRenderer $actionButtonRenderer,
    ) {
    }

    public function render(
        Dialog $dialog,
        string $formModel,
        string $visibleModel,
        FormRenderOptions $options,
        ?RenderContext $context = null,
        ?string $titleExpression = null,
        ?string $iframeUrlExpression = null,
        ?string $loadingExpression = null,
        ?string $beforeCloseExpression = null,
        ?string $closedExpression = null
    ): AbstractHtmlElement {
        $attrs = array_merge([
            'v-model' => $visibleModel,
            'width' => $dialog->getWidth(),
            ':close-on-click-modal' => $dialog->shouldCloseOnClickModal() ? 'true' : 'false',
            ':draggable' => $dialog->isDraggable() ? 'true' : 'false',
            ':fullscreen' => $dialog->isFullscreen() ? 'true' : 'false',
        ], $dialog->attrs());

        if ($titleExpression !== null && $titleExpression !== '') {
            $attrs[':title'] = $titleExpression;
        } else {
            $attrs['title'] = $dialog->title();
        }

        if ($beforeCloseExpression !== null && $beforeCloseExpression !== '') {
            $attrs[':before-close'] = $beforeCloseExpression;
        }

        if ($closedExpression !== null && $closedExpression !== '') {
            $attrs['@closed'] = $closedExpression;
        }

        if ($dialog->shouldDestroyOnClose()) {
            $attrs['destroy-on-close'] = '';
        }

        if ($dialog->isAlignCenter()) {
            $attrs['align-center'] = '';
        }

        $element = El::double('el-dialog')->setAttrs($attrs);
        $element->append($this->renderBody($dialog, $formModel, $options, $context, $iframeUrlExpression, $loadingExpression));

        $footerActions = $this->resolveFooterActions($dialog);
        if ($footerActions) {
            $footer = El::double('template')->setAttr('#footer');
            foreach ($footerActions as $action) {
                $footer->append($this->actionButtonRenderer->render($action));
            }
            $element->append($footer);
        }

        return $element;
    }

    private function renderBody(
        Dialog $dialog,
        string $formModel,
        FormRenderOptions $options,
        ?RenderContext $context,
        ?string $iframeUrlExpression,
        ?string $loadingExpression
    ): AbstractHtmlElement {
        $body = El::double('div');
        if ($loadingExpression !== null && $loadingExpression !== '') {
            $body->setAttr('v-loading', $loadingExpression);
        }

        if ($dialog->getHeight()) {
            $body->setAttr('style', sprintf('min-height:%s', $dialog->getHeight()));
        }

        if ($dialog->getForm()) {
            $body->append($this->formRenderer->render($dialog->getForm(), $formModel, $options));

            return $body;
        }

        if ($dialog->bodyType() === 'iframe') {
            $body->append(El::double('iframe')->setAttrs([
                ':src' => $iframeUrlExpression ?: $this->jsString($dialog->getIframeUrl() ?? ''),
                'style' => sprintf(
                    'width:100%%;height:%s;border:none;display:block',
                    $dialog->getHeight() ?: '70vh'
                ),
            ]));

            return $body;
        }

        $content = $dialog->getContent();
        if ($content instanceof AbstractHtmlElement) {
            $body->append($content);

            return $body;
        }

        if (is_string($content) && $content !== '') {
            $body->append($content);
        }

        return $body;
    }

    /**
     * @return Action[]
     */
    private function resolveFooterActions(Dialog $dialog): array
    {
        $footerActions = $dialog->getFooterActions();
        if ($footerActions) {
            return $footerActions;
        }

        if ($dialog->getForm()) {
            return [
                Action::close('取消', $dialog->key()),
                Action::submit('保存', $dialog->key()),
            ];
        }

        return [];
    }

    private function jsString(string $value): string
    {
        return "'" . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            $value
        ) . "'";
    }
}
