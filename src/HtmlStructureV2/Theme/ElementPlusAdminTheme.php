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
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\DialogRenderStateFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FieldRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderOptions;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderStateFactory;
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
    private ?DialogRenderStateFactory $dialogRenderStateFactory = null;
    private ?FormRenderStateFactory $formRenderStateFactory = null;
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
            $filterState = $this->formRenderStateFactory()->createFilter();
            $filterCard = $this->card('筛选条件');
            $filterCard->append(
                $this->renderForm($page->getFilterForm(), $filterState->model, $filterState->renderOptions)
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
        $state = $this->formRenderStateFactory()->createStandalone($form->key());

        $this->mergeSimpleState($context, $state->simpleRuntimeState($form));
        $this->mergeSimpleFormConfig($context, $state, $form);

        return $this->card('表单')->append(
            $this->renderForm($form, $state->model, $state->renderOptions)
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
        $state = $this->dialogRenderStateFactory()->createManaged(
            $dialog->key(),
            $this->dialogFormRef($dialog->key())
        );

        return $this->dialogRenderer()->render(
            $dialog,
            $state->formModel,
            $state->visibleModel,
            $state->formOptions,
            $context,
            $state->bindings
        );
    }

    private function renderDialog(Dialog $dialog, string $formModel, string $visibleModel, RenderContext $context): AbstractHtmlElement
    {
        $state = $this->dialogRenderStateFactory()->createStandalone();

        return $this->dialogRenderer()->render(
            $dialog,
            $formModel ?: $state->formModel,
            $visibleModel ?: $state->visibleModel,
            $state->formOptions,
            $context,
            $state->bindings
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
            if ($dialog->getForm() !== null) {
                $this->mergeSimpleFormConfig(
                    $context,
                    $this->formRenderStateFactory()->createManagedDialog($dialog->key()),
                    $dialog->getForm()
                );
            }
            $body->append($this->renderManagedDialog($dialog, $context));
        }
    }

    private function buildListRuntime(ListPage $page): string
    {
        return $this->listRuntimeBuilder()->build($page);
    }

    private function mergeSimpleFormConfig(RenderContext $context, \Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderState $state, Form $form): void
    {
        $simpleConfig = $context->get('v2.simple.config', []);
        $this->mergeSimpleConfig($context, [
            'forms' => array_merge(
                $simpleConfig['forms'] ?? [],
                [
                    $state->scope->value() => $state->simpleRuntimeConfig($form),
                ]
            ),
        ]);
    }

    private function dialogFormRef(string $dialogKey): string
    {
        return $this->formRenderStateFactory()->createManagedDialog($dialogKey)->ref ?? 'dialogFormRef';
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

    private function dialogRenderStateFactory(): DialogRenderStateFactory
    {
        return $this->dialogRenderStateFactory ??= new DialogRenderStateFactory();
    }

    private function formRenderStateFactory(): FormRenderStateFactory
    {
        return $this->formRenderStateFactory ??= new FormRenderStateFactory();
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
