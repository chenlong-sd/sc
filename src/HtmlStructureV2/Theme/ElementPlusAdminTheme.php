<?php

namespace Sc\Util\HtmlStructureV2\Theme;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\Page\CrudPage;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\Page\CustomPage;
use Sc\Util\HtmlStructureV2\Page\FormPage;
use Sc\Util\HtmlStructureV2\Page\ListPage;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\ActionButtonRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\ColumnRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\DialogRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FieldRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderOptions;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\DialogConfigBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\ListRuntimeBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\SimpleRuntimeBuilder;

final class ElementPlusAdminTheme implements ThemeInterface
{
    private ?ActionButtonRenderer $actionButtonRenderer = null;
    private ?FieldRenderer $fieldRenderer = null;
    private ?ColumnRenderer $columnRenderer = null;
    private ?FormRenderer $formRenderer = null;
    private ?TableRenderer $tableRenderer = null;
    private ?DialogRenderer $dialogRenderer = null;
    private ?SimpleRuntimeBuilder $simpleRuntimeBuilder = null;
    private ?ListRuntimeBuilder $listRuntimeBuilder = null;
    private ?DialogConfigBuilder $dialogConfigBuilder = null;

    private const BASE_CSS = <<<CSS
    [v-cloak]{display:none}
    html,body{height:100%}
    body{margin:0;background:#f5f7fa;color:#1f2937;font-family:"Helvetica Neue",Helvetica,"PingFang SC","Microsoft YaHei",sans-serif}
    #app{min-height:100%;box-sizing:border-box;padding:24px}
    .sc-v2-page{display:flex;flex-direction:column;gap:18px}
    .sc-v2-page__header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
    .sc-v2-page__title{display:flex;flex-direction:column;gap:6px}
    .sc-v2-page__title h1{margin:0;font-size:28px;line-height:1.2;color:#111827}
    .sc-v2-page__title p{margin:0;color:#6b7280;font-size:14px}
    .sc-v2-actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-section .el-card__body{display:flex;flex-direction:column;gap:16px}
    .sc-v2-section__header{display:flex;justify-content:space-between;align-items:center;gap:12px;font-weight:600}
    .sc-v2-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .sc-v2-toolbar__actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-form__help{margin-top:6px;font-size:12px;line-height:1.5;color:#909399}
    .sc-v2-table__footer{display:flex;justify-content:flex-end;color:#909399;font-size:12px}
    .sc-v2-row-actions{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-filters__actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-table__images{display:flex;gap:8px;flex-wrap:wrap}
    @media (max-width: 768px){
      #app{padding:16px}
      .sc-v2-page__header{flex-direction:column;align-items:stretch}
      .sc-v2-actions,.sc-v2-toolbar,.sc-v2-toolbar__actions,.sc-v2-filters__actions{width:100%}
    }
    CSS;

    public function boot(RenderContext $context): void
    {
        $assets = $context->document()->assets();
        $assets->addStylesheet(StaticResource::ELEMENT_PLUS_CSS);
        $assets->addScript(StaticResource::VUE);
        $assets->addScript(StaticResource::ELEMENT_PLUS_ICON);
        $assets->addScript(StaticResource::ELEMENT_PLUS_JS);
        $assets->addScript(StaticResource::ELEMENT_PLUS_LANG);
        $assets->addScript(StaticResource::AXIOS);
        $assets->addInlineStyle(self::BASE_CSS);
    }

    public function render(Renderable $component, RenderContext $context): AbstractHtmlElement
    {
        return match (true) {
            $component instanceof CrudPage => $this->renderListPage($component, $context),
            $component instanceof ListPage => $this->renderListPage($component, $context),
            $component instanceof FormPage => $this->renderFormPage($component, $context),
            $component instanceof CustomPage => $this->renderCustomPage($component, $context),
            $component instanceof Form => $this->renderStandaloneForm($component, $context),
            $component instanceof Table => $this->renderStandaloneTable($component, $context),
            $component instanceof Dialog => $this->renderDialog($component, 'dialogForm', 'dialogVisible', $context),
            $component instanceof Action => $this->renderActionButton($component),
            default => throw new \InvalidArgumentException('Unsupported V2 renderable: ' . $component::class),
        };
    }

    private function renderListPage(ListPage $page, RenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-page');
        $body->append($this->renderPageHeader($page));

        if ($page->getFilterForm()) {
            $filterCard = $this->card('筛选条件');
            $filterCard->append(
                $this->renderForm($page->getFilterForm(), 'filterModel', [
                    'mode' => 'filters',
                    'ref' => 'filterFormRef',
                    'rules' => 'filterRules',
                    'submitMethod' => 'submitFilters',
                    'resetMethod' => 'resetFilters',
                    'remoteOptionsState' => 'filterOptions',
                    'remoteLoadingState' => 'filterOptionLoading',
                    'remoteLoadMethod' => 'loadFormFieldOptions',
                    'remoteScope' => 'filter',
                    'uploadFilesState' => 'filterUploadFiles',
                    'uploadScope' => 'filter',
                    'uploadSuccessMethod' => 'handleUploadSuccess',
                    'uploadRemoveMethod' => 'handleUploadRemove',
                    'uploadExceedMethod' => 'handleUploadExceed',
                    'uploadPreviewMethod' => 'handleUploadPreview',
                    'linkageMethod' => 'applyFormLinkage',
                ])
            );
            $body->append($filterCard);
        }

        if ($page->getTable()) {
            $tableCard = $this->card();
            if ($page->getTable()->getToolbarActions()) {
                $tableCard->append($this->renderTableToolbar($page->getTable()));
            }
            $tableCard->append($this->renderTable($page->getTable()));
            if ($page->getTable()->usePagination()) {
                $tableCard->append($this->renderPagination($page->getTable()));
            }
            $tableCard->append(
                El::double('div')->addClass('sc-v2-table__footer')->append(
                    El::double('span')->append('共 {{ tableTotal || tableRows.length }} 条数据')
                )
            );
            $body->append($tableCard);
        }

        foreach ($page->getSections() as $section) {
            $body->append($section->render($context));
        }

        foreach ($page->getDialogs() as $dialog) {
            $body->append($this->renderManagedDialog($dialog, $context));
        }

        $context->document()->assets()->addInlineScript($this->buildListRuntime($page));

        return $body;
    }

    private function renderFormPage(FormPage $page, RenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-page');
        $body->append($this->renderPageHeader($page));

        if ($page->getForm()) {
            $body->append($this->renderStandaloneForm($page->getForm(), $context));
        }

        foreach ($page->getSections() as $section) {
            $body->append($section->render($context));
        }

        $this->appendManagedDialogs($body, $page, $context);
        $this->appendSimpleRuntime($context);

        return $body;
    }

    private function renderCustomPage(CustomPage $page, RenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-page');
        $body->append($this->renderPageHeader($page));

        foreach ($page->getSections() as $section) {
            $body->append($section->render($context));
        }

        $this->appendManagedDialogs($body, $page, $context);
        $this->appendSimpleRuntime($context);

        return $body;
    }

    private function renderStandaloneForm(Form $form, RenderContext $context): AbstractHtmlElement
    {
        $scope = $form->key();
        $modelName = $this->jsStateVariable($scope, 'Model');
        $rulesName = $this->jsStateVariable($scope, 'Rules');
        $optionsStateName = $this->jsStateVariable($scope, 'Options');
        $optionLoadingName = $this->jsStateVariable($scope, 'OptionLoading');
        $optionLoadedName = $this->jsStateVariable($scope, 'OptionLoaded');
        $uploadFilesName = $this->jsStateVariable($scope, 'UploadFiles');
        $formRef = $this->jsStateVariable($scope, 'FormRef');

        $this->mergeSimpleState($context, [
            $modelName => $form->defaults(),
            $rulesName => $form->rules(),
            $optionsStateName => $this->buildInitialOptionState($form->remoteOptions()),
            $optionLoadingName => $this->buildFlagState(array_keys($form->remoteOptions())),
            $optionLoadedName => $this->buildFlagState(array_keys($form->remoteOptions())),
            $uploadFilesName => [],
        ]);
        $simpleConfig = $context->get('v2.simple.config', []);
        $this->mergeSimpleConfig($context, [
            'forms' => array_merge(
                $simpleConfig['forms'] ?? [],
                [
                    $scope => [
                        'ref' => $formRef,
                        'modelVar' => $modelName,
                        'rulesVar' => $rulesName,
                        'optionStateVar' => $optionsStateName,
                        'optionLoadingVar' => $optionLoadingName,
                        'optionLoadedVar' => $optionLoadedName,
                        'uploadFilesVar' => $uploadFilesName,
                        'remoteOptions' => $form->remoteOptions(),
                        'selectOptions' => $form->selectOptions(),
                        'linkages' => $form->linkages(),
                        'uploads' => $form->uploads(),
                    ],
                ]
            ),
        ]);

        return $this->card('表单')->append(
            $this->renderForm($form, $modelName, [
                'ref' => $formRef,
                'rules' => $rulesName,
                'remoteOptionsState' => $optionsStateName,
                'remoteLoadingState' => $optionLoadingName,
                'remoteLoadMethod' => 'loadSimpleFormFieldOptions',
                'remoteScope' => $scope,
                'uploadFilesState' => $uploadFilesName,
                'uploadScope' => $scope,
                'uploadSuccessMethod' => 'handleSimpleUploadSuccess',
                'uploadRemoveMethod' => 'handleSimpleUploadRemove',
                'uploadExceedMethod' => 'handleSimpleUploadExceed',
                'uploadPreviewMethod' => 'handleSimpleUploadPreview',
                'linkageMethod' => 'applySimpleFormLinkage',
            ])
        );
    }

    private function renderStandaloneTable(Table $table, RenderContext $context): AbstractHtmlElement
    {
        $rowsName = $table->key() . 'Rows';
        $this->mergeSimpleState($context, [
            $rowsName => $table->getDataSource()?->initialRows() ?? [],
            $table->key() . 'Loading' => false,
        ]);

        return $this->card()->append(
            $this->renderTable($table, $rowsName, $table->key() . 'Loading')
        );
    }

    private function renderPageHeader(AbstractPage $page): AbstractHtmlElement
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
                $actions->append($this->renderActionButton($action));
            }
            $header->append($actions);
        }

        return $header;
    }

    private function renderForm(Form $form, string $modelName, array|FormRenderOptions $options = []): AbstractHtmlElement
    {
        return $this->formRenderer()->render(
            $form,
            $modelName,
            is_array($options) ? FormRenderOptions::fromArray($options) : $options
        );
    }

    private function renderTableToolbar(Table $table): AbstractHtmlElement
    {
        return $this->tableRenderer()->renderToolbar($table);
    }

    private function renderTable(Table $table, string $rowsName = 'tableRows', string $loadingName = 'tableLoading'): AbstractHtmlElement
    {
        return $this->tableRenderer()->renderTable($table, $rowsName, $loadingName);
    }

    private function renderPagination(Table $table): AbstractHtmlElement
    {
        return $this->tableRenderer()->renderPagination($table);
    }

    private function renderManagedDialog(Dialog $dialog, RenderContext $context): AbstractHtmlElement
    {
        $dialogKey = $this->jsLiteral($dialog->key());

        return $this->dialogRenderer()->render(
            $dialog,
            sprintf('dialogForms[%s]', $dialogKey),
            sprintf('dialogVisible[%s]', $dialogKey),
            FormRenderOptions::fromArray([
                'ref' => $this->dialogFormRef($dialog->key()),
                'rules' => sprintf('dialogRules[%s]', $dialogKey),
                'remoteOptionsState' => sprintf('dialogOptions[%s]', $dialogKey),
                'remoteLoadingState' => sprintf('dialogOptionLoading[%s]', $dialogKey),
                'remoteLoadMethod' => 'loadFormFieldOptions',
                'remoteScope' => 'dialog:' . $dialog->key(),
                'uploadFilesState' => sprintf('dialogUploadFiles[%s]', $dialogKey),
                'uploadScope' => 'dialog:' . $dialog->key(),
                'uploadSuccessMethod' => 'handleUploadSuccess',
                'uploadRemoveMethod' => 'handleUploadRemove',
                'uploadExceedMethod' => 'handleUploadExceed',
                'uploadPreviewMethod' => 'handleUploadPreview',
                'linkageMethod' => 'applyFormLinkage',
            ]),
            $context,
            sprintf('dialogTitles[%s]', $dialogKey),
            sprintf('dialogIframeUrls[%s]', $dialogKey),
            sprintf('dialogLoading[%s] || false', $dialogKey),
            sprintf('(done) => handleDialogBeforeClose(%s, done)', $dialogKey),
            sprintf('handleDialogClosed(%s)', $dialogKey)
        );
    }

    private function renderDialog(Dialog $dialog, string $formModel, string $visibleModel, RenderContext $context): AbstractHtmlElement
    {
        return $this->dialogRenderer()->render(
            $dialog,
            $formModel,
            $visibleModel,
            FormRenderOptions::fromArray([
                'ref' => 'dialogFormRef',
                'rules' => 'dialogRules',
                'remoteOptionsState' => 'dialogOptions',
                'remoteLoadingState' => 'dialogOptionLoading',
                'remoteLoadMethod' => 'loadFormFieldOptions',
                'remoteScope' => 'dialog',
                'uploadFilesState' => 'dialogUploadFiles',
                'uploadScope' => 'dialog',
                'uploadSuccessMethod' => 'handleUploadSuccess',
                'uploadRemoveMethod' => 'handleUploadRemove',
                'uploadExceedMethod' => 'handleUploadExceed',
                'uploadPreviewMethod' => 'handleUploadPreview',
                'linkageMethod' => 'applyFormLinkage',
            ]),
            $context
        );
    }

    private function renderActionButton(Action $action, bool $rowScoped = false, string $size = 'default'): AbstractHtmlElement
    {
        return $this->actionButtonRenderer()->render($action, $rowScoped, $size);
    }

    private function mergeSimpleState(RenderContext $context, array $state): void
    {
        $context->set('v2.simple.state', array_merge(
            $context->get('v2.simple.state', []),
            $state
        ));
    }

    private function mergeSimpleConfig(RenderContext $context, array $config): void
    {
        $current = $context->get('v2.simple.config', []);

        $context->set('v2.simple.config', array_replace_recursive($current, $config));
    }

    private function appendSimpleRuntime(RenderContext $context): void
    {
        $this->simpleRuntimeBuilder()->append($context);
    }

    private function appendManagedDialogs(DoubleLabel $body, AbstractPage $page, RenderContext $context): void
    {
        $dialogs = $page->getDialogs();
        if ($dialogs === []) {
            return;
        }

        $this->mergeSimpleConfig($context, [
            'dialogs' => $this->dialogConfigBuilder()->build($dialogs),
        ]);

        foreach ($dialogs as $dialog) {
            $body->append($this->renderManagedDialog($dialog, $context));
        }
    }

    private function buildListRuntime(ListPage $page): string
    {
        return $this->listRuntimeBuilder()->build($page);
    }

    private function buildInitialOptionState(array $remoteOptions): array
    {
        $state = [];
        foreach ($remoteOptions as $fieldName => $fieldConfig) {
            $state[$fieldName] = array_values($fieldConfig['initialOptions'] ?? []);
        }

        return $state;
    }

    private function buildFlagState(array $keys, bool $initial = false): array
    {
        $state = [];
        foreach ($keys as $key) {
            $state[$key] = $initial;
        }

        return $state;
    }

    private function jsStateVariable(string $key, string $suffix): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_$]+/', '_', $key) ?: 'form';
        if (preg_match('/^[0-9]/', $normalized)) {
            $normalized = 'v2_' . $normalized;
        }

        return $normalized . $suffix;
    }

    private function dialogFormRef(string $dialogKey): string
    {
        return 'dialogFormRef_' . $this->jsStateVariable($dialogKey, '');
    }

    private function jsLiteral(string $value): string
    {
        return "'" . str_replace(
            ['\\', '\''],
            ['\\\\', '\\\''],
            $value
        ) . "'";
    }

    private function fieldRenderer(): FieldRenderer
    {
        return $this->fieldRenderer ??= new FieldRenderer();
    }

    private function columnRenderer(): ColumnRenderer
    {
        return $this->columnRenderer ??= new ColumnRenderer();
    }

    private function actionButtonRenderer(): ActionButtonRenderer
    {
        return $this->actionButtonRenderer ??= new ActionButtonRenderer();
    }

    private function formRenderer(): FormRenderer
    {
        return $this->formRenderer ??= new FormRenderer($this->fieldRenderer());
    }

    private function tableRenderer(): TableRenderer
    {
        return $this->tableRenderer ??= new TableRenderer(
            $this->columnRenderer(),
            $this->actionButtonRenderer()
        );
    }

    private function dialogRenderer(): DialogRenderer
    {
        return $this->dialogRenderer ??= new DialogRenderer(
            $this->formRenderer(),
            $this->actionButtonRenderer()
        );
    }

    private function simpleRuntimeBuilder(): SimpleRuntimeBuilder
    {
        return $this->simpleRuntimeBuilder ??= new SimpleRuntimeBuilder();
    }

    private function listRuntimeBuilder(): ListRuntimeBuilder
    {
        return $this->listRuntimeBuilder ??= new ListRuntimeBuilder();
    }

    private function dialogConfigBuilder(): DialogConfigBuilder
    {
        return $this->dialogConfigBuilder ??= new DialogConfigBuilder();
    }

    private function card(string $title = ''): DoubleLabel
    {
        $card = El::double('el-card')->addClass('sc-v2-section');

        if ($title !== '') {
            $card->append(
                El::double('template')->setAttr('#header')->append(
                    El::double('div')->addClass('sc-v2-section__header')->append($title)
                )
            );
        }

        return $card;
    }
}
