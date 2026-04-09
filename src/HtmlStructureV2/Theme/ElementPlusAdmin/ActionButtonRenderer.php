<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class ActionButtonRenderer
{
    use EncodesJsValues;

    public function render(
        Action $action,
        bool $rowScoped = false,
        string $size = 'default',
        ?TableRenderBindings $tableBindings = null,
        ?RenderContext $renderContext = null,
        string $visualVariant = 'default',
        ?string $contextDialogKey = null
    ): AbstractHtmlElement
    {
        if (!$action->isAvailable()) {
            return El::double('template');
        }

        $target = ActionRenderTarget::resolve($action, $tableBindings);
        $this->assertActionHasRequiredRenderTarget($action, $rowScoped, $target);
        $attrs = array_merge([
            'type' => $action->buttonType(),
            'size' => $size,
        ], $action->attrs());

        if (
            $rowScoped
            && !array_key_exists('link', $attrs)
            && !array_key_exists('text', $attrs)
            && !array_key_exists('bg', $attrs)
            && !array_key_exists('plain', $attrs)
        ) {
            $attrs['link'] = '';
        }

        if ($visualVariant === 'page-header') {
            if (
                !array_key_exists('bg', $attrs)
                && !array_key_exists('link', $attrs)
                && !array_key_exists('plain', $attrs)
            ) {
                $attrs['bg'] = '';
            }

            if (
                !array_key_exists('text', $attrs)
                && !array_key_exists('link', $attrs)
                && !array_key_exists('plain', $attrs)
                && !array_key_exists('default', $attrs)
            ) {
                $attrs['text'] = '';
            }
        }

        if ($action->intent() === ActionIntent::REFRESH) {
            $attrs[':loading'] = $target->loadingExpression();
        }

        if ($action instanceof RequestAction) {
            $attrs[':loading'] = $this->requestActionLoadingExpression($action, $target);
        }

        if ($action->intent() === ActionIntent::SUBMIT) {
            $attrs[':loading'] = $this->dialogStateExpression('dialogSubmitting', $action);
            $attrs[':disabled'] = $this->dialogStateExpression('dialogLoading', $action);
        }

        if ($action->intent() === ActionIntent::CLOSE) {
            $attrs[':disabled'] = $this->dialogStateExpression('dialogSubmitting', $action);
        }

        $click = $this->resolveClick($action, $rowScoped, $target, $renderContext, $contextDialogKey);
        if ($click !== null) {
            $attrs['@click'] = $click;
        }

        if (
            $action->iconName() !== null
            && !array_key_exists('icon', $attrs)
            && !array_key_exists(':icon', $attrs)
        ) {
            $attrs['icon'] = $action->iconName();
        }

        $button = El::double('el-button')->setAttrs($this->normalizeRenderableAttributes($attrs));

        $button->append($action->label());

        return $button;
    }

    private function assertActionHasRequiredRenderTarget(
        Action $action,
        bool $rowScoped,
        ActionRenderTarget $target
    ): void {
        if ($action->intent() === ActionIntent::REFRESH && !$target->hasDataTarget()) {
            throw new InvalidArgumentException(sprintf(
                'Action [%s] requires explicit forTable()/forList() or a local table/list render context.',
                $action->label()
            ));
        }

        if (
            $action instanceof RequestAction
            && $action->shouldReloadTable()
            && !$target->hasDataTarget()
        ) {
            throw new InvalidArgumentException(sprintf(
                'Request action [%s] enables reloadTable() but has no explicit forTable()/forList() target or local table/list render context.',
                $action->label()
            ));
        }

        if ($action->intent() === ActionIntent::DELETE && !$target->hasDataTarget()) {
            throw new InvalidArgumentException(sprintf(
                'Action [%s] requires explicit forTable()/forList() or a local table/list render context.',
                $action->label()
            ));
        }

        if ($action->intent() === ActionIntent::DELETE && $rowScoped) {
            throw new InvalidArgumentException(sprintf(
                'Action [%s] is a toolbar batch-delete shortcut and cannot be used in rowActions(); use Actions::request() for single-row delete.',
                $action->label()
            ));
        }
    }

    private function resolveClick(
        Action $action,
        bool $rowScoped,
        ActionRenderTarget $target,
        ?RenderContext $renderContext = null,
        ?string $contextDialogKey = null
    ): ?string
    {
        $actionKey = $this->registerRuntimeActionConfig($action, $target, $renderContext, $contextDialogKey);
        $actionConfig = $this->actionConfig($action, $target, $contextDialogKey);

        if ($action instanceof RequestAction) {
            if ($actionKey !== null) {
                return sprintf(
                    'runRequestAction(%s, %s)',
                    $this->jsString($actionKey),
                    $rowScoped ? 'scope.row' : 'null'
                );
            }

            return sprintf(
                'runRequestAction(%s, %s)',
                $this->jsValue($this->requestActionConfig($action, $target, $contextDialogKey)),
                $rowScoped ? 'scope.row' : 'null'
            );
        }

        return match ($action->intent()) {
            ActionIntent::CREATE => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $this->openDialogExpression($action, null, $target),
                $actionKey !== null
            ),
            ActionIntent::EDIT => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $this->openDialogExpression($action, $rowScoped ? 'scope.row' : 'null', $target),
                $actionKey !== null
            ),
            ActionIntent::DELETE => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $target->deleteSelectionExpression('null', 'context.action', 'context'),
                $actionKey !== null
            ),
            ActionIntent::SUBMIT => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                sprintf(
                    'submitDialog(%s, context.action, context)',
                    $this->jsString($action->targetName() ?: 'editor')
                ),
                $actionKey !== null
            ),
            ActionIntent::CLOSE => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                sprintf('closeDialog(%s)', $this->jsString($action->targetName() ?: 'editor')),
                $actionKey !== null
            ),
            ActionIntent::REFRESH => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $target->reloadExpression(),
                $actionKey !== null
            ),
            ActionIntent::REQUEST => null,
            ActionIntent::CUSTOM => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $this->resolveCustomExecutor($action),
                $actionKey !== null
            ),
        };
    }

    private function resolveCustomExecutor(Action $action): ?string
    {
        $handler = $action->handler();
        if ($handler === null) {
            return null;
        }

        $expression = $handler instanceof \Stringable ? (string)$handler : $handler;
        if (!is_string($expression) || $expression === '') {
            return null;
        }

        return $expression;
    }

    private function requestActionLoadingExpression(RequestAction $action, ActionRenderTarget $target): string
    {
        return sprintf(
            'actionLoading[%s] || false',
            $this->jsString($this->actionLoadingKey($action, $target))
        );
    }

    private function requestActionConfig(
        RequestAction $action,
        ActionRenderTarget $target,
        ?string $contextDialogKey = null
    ): array
    {
        return [
            'label' => $action->label(),
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'contextDialogKey' => $contextDialogKey,
            'confirmText' => $action->confirmText(),
            'events' => $action->getEventHandlers(),
            'successMessage' => $action->getSuccessMessage(),
            'errorMessage' => $action->getErrorMessage(),
            'loadingText' => $action->getLoadingText(),
            'reloadTable' => $action->shouldReloadTable(),
            'reloadPage' => $action->shouldReloadPage(),
            'closeDialog' => $action->shouldCloseAfterSuccess(),
            'dialogTarget' => $action->targetName(),
            'form' => [
                'validate' => $action->shouldValidateForm(),
                'validateScope' => $action->getValidateFormScope(),
                'payloadSource' => $action->getPayloadSource(),
                'payloadScope' => $action->getPayloadFormScope(),
            ],
            'request' => [
                'method' => $action->getRequestMethod(),
                'url' => $action->getRequestUrl(),
                'query' => $action->getPayload(),
            ],
            'save' => [
                'createUrl' => $action->getSaveCreateUrl(),
                'updateUrl' => $action->getSaveUpdateUrl(),
                'modeQueryKey' => $action->getSaveModeQueryKey(),
            ],
        ];
    }

    private function openDialogExpression(
        Action $action,
        ?string $rowExpression,
        ActionRenderTarget $renderTarget
    ): string {
        $dialogTarget = $action->targetName() ?: 'editor';

        return $renderTarget->openDialogExpression($dialogTarget, $rowExpression);
    }

    private function actionKey(Action $action): string
    {
        return $action->getKey() ?: 'sc_action_' . spl_object_id($action);
    }

    private function actionLoadingKey(Action $action, ActionRenderTarget $target): string
    {
        $key = $this->actionKey($action);
        $suffix = $target->loadingKeySuffix();
        if ($suffix === null) {
            return $key;
        }

        return $key . '@' . $suffix;
    }

    private function actionConfig(
        Action $action,
        ActionRenderTarget $target,
        ?string $contextDialogKey = null
    ): array
    {
        return [
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'contextDialogKey' => $contextDialogKey,
            'dialogTarget' => $action->targetName(),
            'confirmText' => $action->confirmText(),
            'events' => $action->getEventHandlers(),
            'submit' => [
                'saveUrl' => $action->getSaveUrl(),
                'createUrl' => $action->getCreateUrl(),
                'updateUrl' => $action->getUpdateUrl(),
            ],
            'delete' => [
                'deleteUrl' => $action->getDeleteUrl(),
                'deleteKey' => $action->getDeleteKey(),
            ],
        ];
    }

    private function wrapActionExecution(
        string $configExpression,
        bool $rowScoped,
        ?string $executor = null,
        bool $registered = false
    ): string
    {
        $rowExpression = $rowScoped ? 'scope.row' : 'null';
        $method = $registered ? 'runAction' : 'runAction';
        if ($executor === null || trim($executor) === '') {
            return sprintf('%s(%s, %s)', $method, $configExpression, $rowExpression);
        }

        return sprintf(
            '%s(%s, %s, (context) => { %s })',
            $method,
            $configExpression,
            $rowExpression,
            $executor
        );
    }

    private function registerRuntimeActionConfig(
        Action $action,
        ActionRenderTarget $target,
        ?RenderContext $renderContext = null,
        ?string $contextDialogKey = null
    ): ?string {
        if ($renderContext === null) {
            return null;
        }

        $key = $this->actionLoadingKey($action, $target);
        $config = $action instanceof RequestAction
            ? $this->requestActionConfig($action, $target, $contextDialogKey)
            : $this->actionConfig($action, $target, $contextDialogKey);

        return (new PageRuntimeRegistry($renderContext))->registerActionConfig($key, $config);
    }

    private function dialogStateExpression(string $stateName, Action $action): string
    {
        return sprintf(
            '%s[%s] || false',
            $stateName,
            $this->jsString($action->targetName() ?: 'editor')
        );
    }
}
