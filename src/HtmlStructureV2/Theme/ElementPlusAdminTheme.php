<?php

namespace Sc\Util\HtmlStructureV2\Theme;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\Page\CrudPage;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\Page\CustomPage;
use Sc\Util\HtmlStructureV2\Page\FormPage;
use Sc\Util\HtmlStructureV2\Page\ListPage;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\PageCompositionInspector;
use Sc\Util\HtmlStructureV2\Support\StaticResource;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\ActionButtonRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\ColumnRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\DialogRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\DialogRenderStateFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FieldRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderOptions;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\FormRenderStateFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\LightweightComponentRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\PageFrameRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\PageRuntimeRegistry;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\PreparedListWidget;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\RuntimePreparationCoordinator;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\SectionCardFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderBindings;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableCardRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderStateFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\DialogConfigBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\ListRuntimeBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\SimpleRuntimeBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\TableRuntimeConfigBuilder;

final class ElementPlusAdminTheme implements ThemeInterface
{
    private const COMPONENT_RENDERERS = [
        CrudPage::class => 'renderListPage',
        ListPage::class => 'renderListPage',
        FormPage::class => 'renderFormPage',
        CustomPage::class => 'renderCustomPage',
        ListWidget::class => 'renderListWidgetComponent',
        Form::class => 'renderStandaloneForm',
        Table::class => 'renderStandaloneTable',
        Dialog::class => 'renderStandaloneDialog',
        Action::class => 'renderStandaloneAction',
    ];

    private ?ActionButtonRenderer $actionButtonRenderer = null;
    private ?FieldRenderer $fieldRenderer = null;
    private ?ColumnRenderer $columnRenderer = null;
    private ?FormRenderer $formRenderer = null;
    private ?TableRenderer $tableRenderer = null;
    private ?DialogRenderer $dialogRenderer = null;
    private ?DialogRenderStateFactory $dialogRenderStateFactory = null;
    private ?FormRenderStateFactory $formRenderStateFactory = null;
    private ?TableRenderStateFactory $tableRenderStateFactory = null;
    private ?SimpleRuntimeBuilder $simpleRuntimeBuilder = null;
    private ?ListRuntimeBuilder $listRuntimeBuilder = null;
    private ?DialogConfigBuilder $dialogConfigBuilder = null;
    private ?TableRuntimeConfigBuilder $tableRuntimeConfigBuilder = null;
    private ?PageCompositionInspector $pageCompositionInspector = null;
    private ?RuntimePreparationCoordinator $runtimePreparationCoordinator = null;
    private ?SectionCardFactory $sectionCardFactory = null;
    private ?TableCardRenderer $tableCardRenderer = null;
    private ?PageFrameRenderer $pageFrameRenderer = null;
    private ?LightweightComponentRenderer $lightweightComponentRenderer = null;

    private const BASE_CSS = <<<CSS
    [v-cloak]{display:none}
    html,body{height:100%}
    body{margin:0;background:#f5f7fa;color:#1f2937;font-family:"Helvetica Neue",Helvetica,"PingFang SC","Microsoft YaHei",sans-serif}
    #app{min-height:100%;box-sizing:border-box;padding:24px}
    .sc-v2-page{display:flex;flex-direction:column;gap:18px}
    .sc-v2-list{display:flex;flex-direction:column;gap:18px}
    .sc-v2-page__header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
    .sc-v2-page__title{display:flex;flex-direction:column;gap:6px}
    .sc-v2-page__title h1{margin:0;font-size:28px;line-height:1.2;color:#111827}
    .sc-v2-page__title p{margin:0;color:#6b7280;font-size:14px}
    .sc-v2-actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-section .el-card__body{display:flex;flex-direction:column;gap:16px}
    .sc-v2-section__header{display:flex;justify-content:space-between;align-items:center;gap:12px;font-weight:600}
    .sc-v2-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .sc-v2-toolbar__actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-form__control{display:flex;align-items:flex-start;gap:12px;width:100%}
    .sc-v2-form__control>:first-child{flex:1 1 auto;min-width:0}
    .sc-v2-form__suffix{display:flex;align-items:center;gap:8px;flex:0 0 auto;flex-wrap:wrap}
    .sc-v2-form__suffix-text{font-size:13px;line-height:1.5;color:#6b7280}
    .sc-v2-form__help{margin-top:6px;font-size:12px;line-height:1.5;color:#909399}
    .sc-v2-table__footer{display:flex;justify-content:flex-end;color:#909399;font-size:12px}
    .sc-v2-row-actions{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-filters__actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-table__images{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-stack{display:flex;flex-direction:column}
    .sc-v2-grid{display:grid}
    .sc-v2-block-title{display:flex;flex-direction:column;gap:6px}
    .sc-v2-block-title h2{margin:0;font-size:22px;line-height:1.25;color:#111827}
    .sc-v2-block-title p{margin:0;color:#6b7280;font-size:14px}
    .sc-v2-block-text{margin:0;line-height:1.7;color:#374151}
    .sc-v2-block-text--muted{color:#6b7280}
    .sc-v2-form-section{display:flex;flex-direction:column;gap:16px}
    .sc-v2-form-section__header{display:flex;flex-direction:column;gap:4px}
    .sc-v2-form-section__header h3{margin:0;font-size:18px;line-height:1.3;color:#111827}
    .sc-v2-form-section__header p{margin:0;color:#6b7280;font-size:13px}
    .sc-v2-form-inline{display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .sc-v2-form-array{display:flex;flex-direction:column;gap:12px}
    .sc-v2-form-array__header{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .sc-v2-form-array__header h4{margin:0;font-size:14px;line-height:1.4;color:#374151}
    .sc-v2-form-array__rows{display:flex;flex-direction:column;gap:12px}
    .sc-v2-form-array__item .el-card__body{display:flex;flex-direction:column;gap:16px}
    .sc-v2-form-array__item-header{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .sc-v2-form-array__item-title{font-size:14px;font-weight:600;color:#374151}
    .sc-v2-form-array__item-actions{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-form-array__item-body{display:flex;flex-direction:column;gap:16px}
    .sc-v2-form-array__footer{display:flex;justify-content:flex-start}
    .sc-v2-form-table{display:flex;flex-direction:column;gap:12px}
    .sc-v2-form-table__header{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .sc-v2-form-table__header h4{margin:0;font-size:14px;line-height:1.4;color:#374151}
    .sc-v2-form-table__footer{display:flex;justify-content:flex-start}
    .sc-v2-form-table__actions{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-form-table__item{margin-bottom:0}
    @media (max-width: 768px){
      #app{padding:16px}
      .sc-v2-page__header{flex-direction:column;align-items:stretch}
      .sc-v2-actions,.sc-v2-toolbar,.sc-v2-toolbar__actions,.sc-v2-filters__actions{width:100%}
      .sc-v2-form__control{flex-direction:column;align-items:stretch}
      .sc-v2-form__suffix{width:100%}
      .sc-v2-grid{grid-template-columns:minmax(0,1fr)!important}
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
        if ($this->lightweightComponentRenderer()->supports($component)) {
            return $this->lightweightComponentRenderer()->render($component, $context);
        }

        $method = $this->resolveRenderableRendererMethod($component);
        if ($method === null) {
            throw new \InvalidArgumentException('Unsupported V2 renderable: ' . $component::class);
        }

        return $this->{$method}($component, $context);
    }

    private function renderListPage(ListPage $page, RenderContext $context): AbstractHtmlElement
    {
        $list = $page->toListWidget();
        $prepared = $this->runtimePreparationCoordinator()->prepareListWidget(
            $this->runtimeRegistry($context),
            $list,
            [
            'deleteUrl' => $page->getDeleteUrl() ?? $page->getTable()?->getDeleteUrl(),
            'deleteKey' => $page->getDeleteUrl() !== null
                ? $page->getDeleteKey()
                : $page->getTable()?->getDeleteKey(),
            ],
            true
        );
        $renderedSections = $this->renderSections($page->getSections(), $context);
        $managedDialogs = $this->collectPageManagedDialogs($page, array_merge([$list], $page->getSections()));
        $this->validatePageActionTargets($page, $context, array_merge([$list], $page->getSections()), $managedDialogs);

        $body = $this->pageFrameRenderer()->render(
            $page,
            $renderedSections,
            $this->renderPreparedListWidget($list, $context, $prepared),
            $prepared->tableState?->bindings
        );

        $this->appendManagedDialogs($body, $context, $managedDialogs);
        $this->appendListRuntime($context);

        return $body;
    }

    private function renderFormPage(FormPage $page, RenderContext $context): AbstractHtmlElement
    {
        $renderedSections = $this->renderSections($page->getSections(), $context);
        $managedDialogs = $this->collectPageManagedDialogs($page, $page->getSections());
        $this->validatePageActionTargets($page, $context, $page->getSections(), $managedDialogs);

        $body = $this->pageFrameRenderer()->render(
            $page,
            $renderedSections,
            $page->getForm() ? $this->renderStandaloneForm($page->getForm(), $context) : null
        );

        $this->appendManagedDialogs($body, $context, $managedDialogs);
        $this->appendPageRuntime($context);

        return $body;
    }

    private function renderCustomPage(CustomPage $page, RenderContext $context): AbstractHtmlElement
    {
        $renderedSections = $this->renderSections($page->getSections(), $context);
        $managedDialogs = $this->collectPageManagedDialogs($page, $page->getSections());
        $this->validatePageActionTargets($page, $context, $page->getSections(), $managedDialogs);

        $body = $this->pageFrameRenderer()->render($page, $renderedSections);

        $this->appendManagedDialogs($body, $context, $managedDialogs);
        $this->appendPageRuntime($context);

        return $body;
    }

    private function renderStandaloneForm(Form $form, RenderContext $context): AbstractHtmlElement
    {
        $state = $this->formRenderStateFactory()->createStandalone($form->key());

        $this->runtimePreparationCoordinator()->registerSimpleFormRuntime(
            $this->runtimeRegistry($context),
            $state,
            $form
        );

        return $this->sectionCardFactory()->make('表单')->append(
            $this->renderForm($form, $state->model, $state->renderOptions)
        );
    }

    private function renderStandaloneTable(Table $table, RenderContext $context): AbstractHtmlElement
    {
        $state = $this->runtimePreparationCoordinator()->prepareTable(
            $this->runtimeRegistry($context),
            $table
        );
        return $this->tableCardRenderer()->render($table, $state->bindings, true);
    }

    private function renderListWidgetComponent(ListWidget $list, RenderContext $context): AbstractHtmlElement
    {
        return $this->renderPreparedListWidget(
            $list,
            $context,
            $this->runtimePreparationCoordinator()->prepareListWidget(
                $this->runtimeRegistry($context),
                $list
            )
        );
    }

    private function renderForm(Form $form, string $modelName, array|FormRenderOptions $options = []): AbstractHtmlElement
    {
        return $this->formRenderer()->render(
            $form,
            $modelName,
            is_array($options) ? FormRenderOptions::fromArray($options) : $options
        );
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

    private function renderStandaloneDialog(Dialog $dialog, RenderContext $context): AbstractHtmlElement
    {
        return $this->renderDialog($dialog, 'dialogForm', 'dialogVisible', $context);
    }

    private function renderActionButton(
        Action $action,
        bool $rowScoped = false,
        string $size = 'default',
        ?TableRenderBindings $tableBindings = null
    ): AbstractHtmlElement
    {
        return $this->actionButtonRenderer()->render($action, $rowScoped, $size, $tableBindings);
    }

    private function renderStandaloneAction(Action $action, RenderContext $context): AbstractHtmlElement
    {
        return $this->renderActionButton($action);
    }

    /**
     * @param Renderable[] $sections
     * @return AbstractHtmlElement[]
     */
    private function renderSections(array $sections, RenderContext $context): array
    {
        return array_map(
            static fn (Renderable $section): AbstractHtmlElement => $section->render($context),
            $sections
        );
    }

    private function appendSimpleRuntime(RenderContext $context): void
    {
        $this->simpleRuntimeBuilder()->append($context);
    }

    private function appendPageRuntime(RenderContext $context): void
    {
        if ($this->runtimeRegistry($context)->requiresListRuntime()) {
            $this->appendListRuntime($context);

            return;
        }

        $this->appendSimpleRuntime($context);
    }

    /**
     * @param Dialog[] $dialogs
     */
    private function appendManagedDialogs(DoubleLabel $body, RenderContext $context, array $dialogs): void
    {
        foreach ($this->runtimePreparationCoordinator()->prepareManagedDialogs($this->runtimeRegistry($context), $dialogs) as $dialog) {
            $body->append($this->renderManagedDialog($dialog, $context));
        }
    }

    private function appendListRuntime(RenderContext $context): void
    {
        $this->listRuntimeBuilder()->append($context);
    }

    private function dialogFormRef(string $dialogKey): string
    {
        return $this->formRenderStateFactory()->createManagedDialog($dialogKey)->ref ?? 'dialogFormRef';
    }

    private function renderPreparedListWidget(
        ListWidget $list,
        RenderContext $context,
        PreparedListWidget $prepared
    ): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-list');

        $filterForm = $list->getFilterForm();
        $filterState = $prepared->filterState;
        if ($filterForm !== null && $filterState !== null) {
            $filterCard = $this->sectionCardFactory()->make($list->getFilterTitle());
            $filterCard->append(
                $this->renderForm($filterForm, $filterState->model, $filterState->renderOptions)
            );
            $body->append($filterCard);
        }

        $table = $list->getTable();
        $tableState = $prepared->tableState;
        if ($table !== null && $tableState !== null) {
            $body->append(
                $this->tableCardRenderer()->render($table, $tableState->bindings, $list->shouldShowSummary())
            );
        }

        foreach ($prepared->dialogs as $dialog) {
            $body->append($this->renderManagedDialog($dialog, $context));
        }

        return $body;
    }

    /**
     * @param Renderable[] $components
     */
    private function validatePageActionTargets(
        AbstractPage $page,
        RenderContext $context,
        array $components,
        array $dialogs
    ): void
    {
        $this->pageCompositionInspector()->validateActionTargets(
            $page,
            $components,
            $dialogs,
            $this->runtimeRegistry($context)->tableKeys(),
            $this->runtimeRegistry($context)->listKeys()
        );
    }

    /**
     * @param Renderable[] $components
     * @return Dialog[]
     */
    private function collectPageManagedDialogs(AbstractPage $page, array $components): array
    {
        return $this->pageCompositionInspector()->collectManagedDialogs($page, $components);
    }

    private function resolveRenderableRendererMethod(Renderable $component): ?string
    {
        foreach (self::COMPONENT_RENDERERS as $class => $method) {
            if ($component instanceof $class) {
                return $method;
            }
        }

        return null;
    }

    private function pageCompositionInspector(): PageCompositionInspector
    {
        return $this->pageCompositionInspector ??= new PageCompositionInspector();
    }

    private function pageFrameRenderer(): PageFrameRenderer
    {
        return $this->pageFrameRenderer ??= new PageFrameRenderer($this->actionButtonRenderer());
    }

    private function runtimeRegistry(RenderContext $context): PageRuntimeRegistry
    {
        return new PageRuntimeRegistry($context);
    }

    private function runtimePreparationCoordinator(): RuntimePreparationCoordinator
    {
        return $this->runtimePreparationCoordinator ??= new RuntimePreparationCoordinator(
            $this->formRenderStateFactory(),
            $this->tableRenderStateFactory(),
            $this->dialogConfigBuilder(),
            $this->tableRuntimeConfigBuilder()
        );
    }

    private function sectionCardFactory(): SectionCardFactory
    {
        return $this->sectionCardFactory ??= new SectionCardFactory();
    }

    private function tableCardRenderer(): TableCardRenderer
    {
        return $this->tableCardRenderer ??= new TableCardRenderer(
            $this->tableRenderer(),
            $this->sectionCardFactory()
        );
    }

    private function lightweightComponentRenderer(): LightweightComponentRenderer
    {
        return $this->lightweightComponentRenderer ??= new LightweightComponentRenderer(
            $this->sectionCardFactory()
        );
    }

    private function fieldRenderer(): FieldRenderer
    {
        return $this->fieldRenderer ??= new FieldRenderer($this->actionButtonRenderer());
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

    private function tableRenderStateFactory(): TableRenderStateFactory
    {
        return $this->tableRenderStateFactory ??= new TableRenderStateFactory();
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

    private function tableRuntimeConfigBuilder(): TableRuntimeConfigBuilder
    {
        return $this->tableRuntimeConfigBuilder ??= new TableRuntimeConfigBuilder();
    }

}
