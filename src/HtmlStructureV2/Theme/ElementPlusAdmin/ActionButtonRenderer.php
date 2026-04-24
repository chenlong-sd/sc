<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\RequestAction;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
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
        ?string $contextDialogKey = null,
        ?string $formScope = null
    ): AbstractHtmlElement
    {
        if (!$action->isAvailable()) {
            return El::double('template');
        }

        if ($action instanceof RequestAction && $action->usesImport()) {
            $renderContext?->document()->assets()->addScript(StaticResource::XLSX);
        }

        $target = ActionRenderTarget::resolve($action, $tableBindings);
        $this->assertActionHasRequiredRenderTarget($action, $rowScoped, $target, $contextDialogKey);
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
            $attrs[':loading'] = $this->dialogStateExpression('dialogSubmitting', $action, $contextDialogKey);
            $attrs[':disabled'] = $this->dialogStateExpression('dialogLoading', $action, $contextDialogKey);
            $attrs = $this->mergeConditionalAttribute(
                $attrs,
                'v-if',
                $this->dialogSubmitVisibleExpression($action, $contextDialogKey)
            );
        }

        if ($action->intent() === ActionIntent::CLOSE) {
            $attrs[':disabled'] = $this->dialogStateExpression('dialogSubmitting', $action, $contextDialogKey);
        }

        $click = $this->resolveClick($action, $rowScoped, $target, $renderContext, $contextDialogKey, $formScope);
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
        ActionRenderTarget $target,
        ?string $contextDialogKey = null
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
            && trim((string) ($contextDialogKey ?? '')) === ''
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
        ?string $contextDialogKey = null,
        ?string $formScope = null
    ): ?string
    {
        $actionKey = $this->registerRuntimeActionConfig($action, $target, $renderContext, $contextDialogKey, $formScope);
        $actionConfig = $this->actionConfig($action, $target, $contextDialogKey, $formScope);

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
                $this->jsValue($this->requestActionConfig($action, $target, $contextDialogKey, $formScope)),
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
                    $this->jsString($this->resolveDialogTarget($action, $contextDialogKey))
                ),
                $actionKey !== null
            ),
            ActionIntent::CLOSE => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                sprintf('closeDialog(%s)', $this->jsString($this->resolveDialogTarget($action, $contextDialogKey))),
                $actionKey !== null
            ),
            ActionIntent::REFRESH => $this->wrapActionExecution(
                $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                $rowScoped,
                $target->reloadExpression(),
                $actionKey !== null
            ),
            ActionIntent::REQUEST => null,
            ActionIntent::CUSTOM => $this->resolveCustomExecutor($action) ?? (
                $action->targetName() !== null
                    ? $this->wrapActionExecution(
                        $actionKey !== null ? $this->jsString($actionKey) : $this->jsValue($actionConfig),
                        $rowScoped,
                        $this->openDialogExpression($action, $rowScoped ? 'scope.row' : 'null', $target),
                        $actionKey !== null
                    )
                    : null
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
        ?string $contextDialogKey = null,
        ?string $formScope = null
    ): array
    {
        return [
            'label' => $action->label(),
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'contextDialogKey' => $contextDialogKey,
            'formScope' => $formScope,
            'confirmText' => $action->confirmText(),
            'events' => $action->getEventHandlers(),
            'successMessage' => $action->getSuccessMessage(),
            'errorMessage' => $action->getErrorMessage(),
            'loadingText' => $action->getLoadingText(),
            'reloadTable' => $action->shouldReloadTable(),
            'reloadPage' => $action->shouldReloadPage(),
            'closeDialog' => $action->shouldCloseAfterSuccess(),
            'dialogSubmitFallback' => $action->shouldUseDialogSubmitFallback(),
            'dialogTarget' => $this->resolveActionDialogTarget($action, $contextDialogKey),
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
            'import' => [
                'enabled' => $action->usesImport(),
                'columns' => $action->getImportColumns(),
                'rowsKey' => $action->getImportRowsKey(),
                'columnInfoKey' => $action->getImportColumnInfoKey(),
                'accept' => $action->getImportAccept(),
                'headerRow' => $action->getImportHeaderRow(),
                'dialogTitle' => $action->getImportDialogTitle(),
                'templateFileName' => $action->getImportTemplateFileName(),
                'jsonEnabled' => $action->isImportJsonEnabled(),
                'aiPromptEnabled' => $action->isImportAiPromptEnabled(),
                'aiPromptText' => $action->getImportAiPromptText(),
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
        ?string $contextDialogKey = null,
        ?string $formScope = null
    ): array
    {
        return [
            'key' => $this->actionLoadingKey($action, $target),
            'tableKey' => $target->tableKey(),
            'listKey' => $target->listKey(),
            'contextDialogKey' => $contextDialogKey,
            'formScope' => $formScope,
            'dialogTarget' => $this->resolveActionDialogTarget($action, $contextDialogKey),
            'confirmText' => $action->confirmText(),
            'successMessage' => $action->getSuccessMessage(),
            'errorMessage' => $action->getErrorMessage(),
            'loadingText' => $action->getLoadingText(),
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

    private function resolveActionDialogTarget(Action $action, ?string $contextDialogKey = null): ?string
    {
        if (
            in_array($action->intent(), [ActionIntent::SUBMIT, ActionIntent::CLOSE], true)
            || $action instanceof RequestAction
        ) {
            return $this->resolveDialogTarget($action, $contextDialogKey);
        }

        $target = trim((string) ($action->targetName() ?? ''));

        return $target !== '' ? $target : null;
    }

    private function resolveDialogTarget(Action $action, ?string $contextDialogKey = null): string
    {
        $target = trim((string) ($action->targetName() ?? ''));
        if ($target !== '') {
            return $target;
        }

        $contextTarget = trim((string) ($contextDialogKey ?? ''));
        if ($contextTarget !== '') {
            return $contextTarget;
        }

        return 'editor';
    }

    private function registerRuntimeActionConfig(
        Action $action,
        ActionRenderTarget $target,
        ?RenderContext $renderContext = null,
        ?string $contextDialogKey = null,
        ?string $formScope = null
    ): ?string {
        if ($renderContext === null) {
            return null;
        }

        $key = $this->actionLoadingKey($action, $target);
        $config = $action instanceof RequestAction
            ? $this->requestActionConfig($action, $target, $contextDialogKey, $formScope)
            : $this->actionConfig($action, $target, $contextDialogKey, $formScope);

        return (new PageRuntimeRegistry($renderContext))->registerActionConfig($key, $config);
    }

    private function dialogStateExpression(string $stateName, Action $action, ?string $contextDialogKey = null): string
    {
        return sprintf(
            '%s[%s] || false',
            $stateName,
            $this->jsString($this->resolveDialogTarget($action, $contextDialogKey))
        );
    }

    private function dialogSubmitVisibleExpression(Action $action, ?string $contextDialogKey = null): string
    {
        return sprintf(
            'isDialogSubmitVisible(%s)',
            $this->jsString($this->resolveDialogTarget($action, $contextDialogKey))
        );
    }

    private function mergeConditionalAttribute(array $attrs, string $attribute, string $expression): array
    {
        $existing = $attrs[$attribute] ?? null;
        if (!is_string($existing) || trim($existing) === '') {
            $attrs[$attribute] = $expression;

            return $attrs;
        }

        $attrs[$attribute] = sprintf('(%s) && (%s)', $existing, $expression);

        return $attrs;
    }
}
