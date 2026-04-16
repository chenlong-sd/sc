<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class DialogRenderer
{
    use EncodesJsValues;

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
        ?DialogRenderBindings $bindings = null
    ): AbstractHtmlElement {
        $bindings ??= DialogRenderBindings::make();
        $attrs = array_merge([
            'v-model' => $visibleModel,
            'width' => $dialog->getWidth(),
            ':close-on-click-modal' => $dialog->shouldCloseOnClickModal() ? 'true' : 'false',
            ':draggable' => $dialog->isDraggable() ? 'true' : 'false',
        ], $dialog->attrs());

        $attrs[':fullscreen'] = $bindings->fullscreenExpression() !== null && $bindings->fullscreenExpression() !== ''
            ? $bindings->fullscreenExpression()
            : ($dialog->isFullscreen() ? 'true' : 'false');

        if ($bindings->titleExpression() !== null && $bindings->titleExpression() !== '') {
            $attrs[':title'] = $bindings->titleExpression();
        } else {
            $attrs['title'] = $dialog->title();
        }

        if ($bindings->beforeCloseExpression() !== null && $bindings->beforeCloseExpression() !== '') {
            $attrs[':before-close'] = $bindings->beforeCloseExpression();
        }

        if ($bindings->closedExpression() !== null && $bindings->closedExpression() !== '') {
            $attrs['@closed'] = $bindings->closedExpression();
        }

        if ($dialog->shouldDestroyOnClose()) {
            $attrs['destroy-on-close'] = '';
        }

        if ($dialog->isAlignCenter()) {
            $attrs['align-center'] = '';
        }

        $element = El::double('el-dialog')->setAttrs($this->normalizeRenderableAttributes($attrs));
        if ($dialog->bodyType() === 'iframe'
            && $bindings->toggleFullscreenExpression() !== null
            && $bindings->toggleFullscreenExpression() !== '') {
            $header = El::double('template')->setAttr('#header', '{ titleId, titleClass }')
                ->append(
                    El::double('div')->setAttrs([
                        'style' => 'display:flex;align-items:center;justify-content:space-between;gap:12px;',
                    ])->append(
                        El::double('span')->setAttrs([
                            ':id' => 'titleId',
                            ':class' => 'titleClass',
                        ])->append(
                            $bindings->titleExpression() !== null && $bindings->titleExpression() !== ''
                                ? '{{ ' . $bindings->titleExpression() . ' }}'
                                : $dialog->title()
                        )
                    )->append(
                        El::double('el-button')->setAttrs([
                            'link' => '',
                            'type' => 'primary',
                            '@click' => $bindings->toggleFullscreenExpression(),
                        ])->append('全屏切换')
                    )
                );
            $element->append($header);
        }

        $element->append($this->renderBody(
            $dialog,
            $formModel,
            $options,
            $context,
            $bindings
        ));

        $footerActions = $this->resolveFooterActions($dialog);
        if ($footerActions) {
            $footer = El::double('template')->setAttr('#footer');
            $footerScope = El::double('template')->setAttr('v-for', $this->dialogRowScopeExpression($dialog));
            foreach ($footerActions as $action) {
                $footerScope->append($this->actionButtonRenderer->render(
                    $action,
                    false,
                    'default',
                    null,
                    $context,
                    'default',
                    $dialog->key()
                ));
            }
            $footer->append($footerScope);
            $element->append($footer);
        }

        return $element;
    }

    private function renderBody(
        Dialog $dialog,
        string $formModel,
        FormRenderOptions $options,
        ?RenderContext $context,
        DialogRenderBindings $bindings
    ): AbstractHtmlElement {
        $body = El::double('div')->setAttr('v-for', $this->dialogRowScopeExpression($dialog));
        if ($bindings->loadingExpression() !== null && $bindings->loadingExpression() !== '') {
            $body->setAttr('v-loading', $bindings->loadingExpression());
        }

        if ($dialog->getHeight()) {
            $body->setAttr('style', sprintf('min-height:%s', $dialog->getHeight()));
        }

        if ($dialog->getForm()) {
            $body->append($this->formRenderer->render($dialog->getForm(), $formModel, $options, $context));

            return $body;
        }

        if ($dialog->bodyType() === 'component' && $dialog->getComponentName()) {
            $component = El::double('component')->setAttr(':is', $this->jsString($dialog->getComponentName()));
            $component->setAttr('v-bind', $bindings->componentPropsExpression() ?: $this->jsValue($dialog->getComponentProps()));

            foreach ($dialog->getComponentAttrs() as $key => $value) {
                $component->setAttr($key, $this->jsAttributeValue($value));
            }

            if ($bindings->componentRefExpression() !== null && $bindings->componentRefExpression() !== '') {
                $component->setAttr(':ref', $bindings->componentRefExpression());
            }

            $body->append($component);

            return $body;
        }

        if ($dialog->bodyType() === 'iframe') {
            $iframe = El::double('iframe')->setAttrs([
                ':src' => $bindings->iframeUrlExpression() ?: $this->jsString($dialog->getIframeUrl() ?? ''),
                'style' => sprintf(
                    'width:100%%;height:%s;border:none;display:block',
                    $dialog->getHeight() ?: 'auto'
                ),
            ]);

            if ($bindings->iframeRefExpression() !== null && $bindings->iframeRefExpression() !== '') {
                $iframe->setAttr(':ref', $bindings->iframeRefExpression());
            }

            if ($bindings->iframeLoadExpression() !== null && $bindings->iframeLoadExpression() !== '') {
                $iframe->setAttr('@load', $bindings->iframeLoadExpression());
            }

            $body->append($iframe);

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

    private function dialogRowScopeExpression(Dialog $dialog): string
    {
        // Expose the current dialog row as a local alias so business pages can use
        // `dialogRow` directly in dialog body/footer expressions instead of repeating
        // `dialogRows['dialog-key']`.
        return sprintf(
            'dialogRow in [dialogRows[%s] || null]',
            $this->jsString($dialog->key())
        );
    }

    /**
     * @return Action[]
     */
    private function resolveFooterActions(Dialog $dialog): array
    {
        $form = $dialog->getForm();
        $formReadonly = $form?->isReadonly() ?? false;
        $footerActions = $dialog->getFooterActions();
        if ($footerActions) {
            if (!$formReadonly) {
                return $footerActions;
            }

            $filteredActions = array_values(array_filter(
                $footerActions,
                static fn(Action $action): bool => $action->intent() !== ActionIntent::SUBMIT
            ));

            return $filteredActions !== []
                ? $filteredActions
                : [Action::close('关闭')->dialog($dialog->key())];
        }

        if ($form) {
            if ($formReadonly) {
                return [
                    Action::close('关闭')->dialog($dialog->key()),
                ];
            }

            return [
                Action::close('取消')->dialog($dialog->key()),
                Action::submit('保存')->dialog($dialog->key()),
            ];
        }

        return [];
    }
}
