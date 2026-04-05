<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class ActionButtonRenderer
{
    use EncodesJsValues;

    public function render(
        Action $action,
        bool $rowScoped = false,
        string $size = 'default',
        ?TableRenderBindings $tableBindings = null
    ): AbstractHtmlElement
    {
        $target = ActionRenderTarget::resolve($action, $tableBindings);
        $this->assertActionHasRequiredRenderTarget($action, $rowScoped, $target);
        $attrs = array_merge([
            'type' => $action->buttonType(),
            'size' => $size,
        ], $action->attrs());

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

        $click = $this->resolveClick($action, $rowScoped, $target);
        if ($click !== null) {
            $attrs['@click'] = $click;
        }

        $button = El::double('el-button')->setAttrs($attrs);

        if ($action->iconName()) {
            $button->append(
                El::double('el-icon')->append(
                    El::double($action->iconName())
                )
            );
        }

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
    }

    private function resolveClick(Action $action, bool $rowScoped, ActionRenderTarget $target): ?string
    {
        $actionConfig = $this->actionConfig($action, $target);

        if ($action instanceof RequestAction) {
            return sprintf(
                'runRequestAction(%s, %s)',
                $this->requestActionConfig($action, $target),
                $rowScoped ? 'scope.row' : 'null'
            );
        }

        return match ($action->intent()) {
            ActionIntent::CREATE => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                $this->openDialogExpression($action, null, $target)
            ),
            ActionIntent::EDIT => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                $this->openDialogExpression($action, $rowScoped ? 'scope.row' : 'null', $target)
            ),
            ActionIntent::DELETE => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                $target->deleteRowExpression($rowScoped ? 'scope.row' : 'null', 'null')
            ),
            ActionIntent::SUBMIT => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                sprintf('submitDialog(%s)', $this->jsString($action->targetName() ?: 'editor'))
            ),
            ActionIntent::CLOSE => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                sprintf('closeDialog(%s)', $this->jsString($action->targetName() ?: 'editor'))
            ),
            ActionIntent::REFRESH => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                $target->reloadExpression()
            ),
            ActionIntent::REQUEST => null,
            ActionIntent::CUSTOM => $this->wrapActionExecution(
                $actionConfig,
                $rowScoped,
                $this->resolveCustomExecutor($action)
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

    private function requestActionConfig(RequestAction $action, ActionRenderTarget $target): string
    {
        return $this->jsValue([
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'confirmText' => $action->confirmText(),
            'events' => $action->getEventHandlers(),
            'successMessage' => $action->getSuccessMessage(),
            'errorMessage' => $action->getErrorMessage(),
            'loadingText' => $action->getLoadingText(),
            'reloadTable' => $action->shouldReloadTable(),
            'reloadPage' => $action->shouldReloadPage(),
            'closeDialog' => $action->shouldCloseAfterSuccess(),
            'dialogTarget' => $action->targetName(),
            'request' => [
                'method' => $action->getRequestMethod(),
                'url' => $action->getRequestUrl(),
                'query' => $action->getPayload(),
            ],
        ]);
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

    private function actionConfig(Action $action, ActionRenderTarget $target): string
    {
        return $this->jsValue([
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'dialogTarget' => $action->targetName(),
            'confirmText' => $action->confirmText(),
            'events' => $action->getEventHandlers(),
        ]);
    }

    private function wrapActionExecution(string $config, bool $rowScoped, ?string $executor = null): string
    {
        $rowExpression = $rowScoped ? 'scope.row' : 'null';
        if ($executor === null || trim($executor) === '') {
            return sprintf('runAction(%s, %s)', $config, $rowExpression);
        }

        return sprintf(
            'runAction(%s, %s, () => { %s })',
            $config,
            $rowExpression,
            $executor
        );
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
