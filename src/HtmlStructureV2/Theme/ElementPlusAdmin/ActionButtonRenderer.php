<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class ActionButtonRenderer
{
    use EncodesJsValues;

    public function render(Action $action, bool $rowScoped = false, string $size = 'default'): AbstractHtmlElement
    {
        $attrs = array_merge([
            'type' => $action->buttonType(),
            'size' => $size,
        ], $action->attrs());

        if ($action->intent() === ActionIntent::REFRESH) {
            $attrs[':loading'] = 'tableLoading';
        }

        if ($action instanceof RequestAction) {
            $attrs[':loading'] = $this->requestActionLoadingExpression($action);
        }

        if ($action->intent() === ActionIntent::SUBMIT) {
            $attrs[':loading'] = $this->dialogStateExpression('dialogSubmitting', $action);
            $attrs[':disabled'] = $this->dialogStateExpression('dialogLoading', $action);
        }

        if ($action->intent() === ActionIntent::CLOSE) {
            $attrs[':disabled'] = $this->dialogStateExpression('dialogSubmitting', $action);
        }

        $click = $this->resolveClick($action, $rowScoped);
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

    private function resolveClick(Action $action, bool $rowScoped): ?string
    {
        if ($action instanceof RequestAction) {
            return sprintf(
                'runRequestAction(%s, %s)',
                $this->requestActionConfig($action),
                $rowScoped ? 'scope.row' : 'null'
            );
        }

        return match ($action->intent()) {
            ActionIntent::CREATE => sprintf('openDialog(%s)', $this->jsString($action->targetName() ?: 'editor')),
            ActionIntent::EDIT => sprintf('openDialog(%s, %s)', $this->jsString($action->targetName() ?: 'editor'), $rowScoped ? 'scope.row' : 'null'),
            ActionIntent::DELETE => sprintf(
                'deleteRow(%s, %s)',
                $rowScoped ? 'scope.row' : 'null',
                $this->jsString($action->confirmText() ?: '确认删除当前记录？')
            ),
            ActionIntent::SUBMIT => sprintf('submitDialog(%s)', $this->jsString($action->targetName() ?: 'editor')),
            ActionIntent::CLOSE => sprintf('closeDialog(%s)', $this->jsString($action->targetName() ?: 'editor')),
            ActionIntent::REFRESH => 'loadTableData()',
            ActionIntent::REQUEST => null,
            ActionIntent::CUSTOM => $this->resolveCustomClick($action),
        };
    }

    private function resolveCustomClick(Action $action): ?string
    {
        $handler = $action->handler();
        if ($handler === null) {
            return null;
        }

        $expression = $handler instanceof \Stringable ? (string)$handler : $handler;
        if (!is_string($expression) || $expression === '') {
            return null;
        }

        $confirmText = $action->confirmText();
        if ($confirmText === null || $confirmText === '') {
            return $expression;
        }

        return sprintf(
            '(() => ElementPlus.ElMessageBox.confirm(%s, "提示", { type: "warning" }).then(() => { %s }).catch((error) => { if (error !== "cancel" && error !== "close") { ElementPlus.ElMessage.error(error?.message || "操作失败"); } }))()',
            $this->jsString($confirmText),
            $expression
        );
    }

    private function requestActionLoadingExpression(RequestAction $action): string
    {
        return sprintf(
            'actionLoading[%s] || false',
            $this->jsString($this->actionKey($action))
        );
    }

    private function requestActionConfig(RequestAction $action): string
    {
        return $this->jsValue([
            'key' => $this->actionKey($action),
            'confirmText' => $action->confirmText(),
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
                'before' => $action->getBeforeHook(),
                'afterSuccess' => $action->getAfterSuccessHook(),
                'afterFail' => $action->getAfterFailHook(),
                'afterFinally' => $action->getAfterFinallyHook(),
            ],
        ]);
    }

    private function actionKey(Action $action): string
    {
        return $action->getKey() ?: 'sc_action_' . spl_object_id($action);
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
