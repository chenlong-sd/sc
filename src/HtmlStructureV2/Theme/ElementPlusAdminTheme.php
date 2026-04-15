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
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\Page\Page;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\PageCompositionInspector;
use Sc\Util\HtmlStructureV2\Support\ResolvesClassMappedMethod;
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
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableBlockRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderer;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\TableRenderStateFactory;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\DialogConfigBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\ListRuntimeBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\RuntimeAssetPublisher;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\RuntimeStyleLoader;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\SimpleRuntimeBuilder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime\TableRuntimeConfigBuilder;

final class ElementPlusAdminTheme implements ThemeInterface
{
    use ResolvesClassMappedMethod;

    private const COMPONENT_RENDERERS = [
        Page::class => 'renderPage',
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
    private ?TableBlockRenderer $tableBlockRenderer = null;
    private ?PageFrameRenderer $pageFrameRenderer = null;
    private ?LightweightComponentRenderer $lightweightComponentRenderer = null;
    private ?RuntimeAssetPublisher $runtimeAssetPublisher = null;

    public function boot(RenderContext $context): void
    {
        $assets = $context->document()->assets();
        $assets->addStylesheet(StaticResource::ELEMENT_PLUS_CSS);
        $this->appendThemeStylesheet($context);
        $assets->addScript(StaticResource::VUE);
        $assets->addScript(StaticResource::ELEMENT_PLUS_ICON);
        $assets->addScript(StaticResource::ELEMENT_PLUS_JS);
        $assets->addScript(StaticResource::ELEMENT_PLUS_LANG);
        $assets->addScript(StaticResource::AXIOS);
        $assets->addScript(StaticResource::SORTABLE);
        $context->document()->appendToBody($this->createAppLoadingShell());
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

    private function renderPage(Page $page, RenderContext $context): AbstractHtmlElement
    {
        $renderedSections = $this->renderSections($page->getSections(), $context);
        $managedDialogs = $this->collectPageManagedDialogs($page, $page->getSections());
        $this->validatePageActionTargets($page, $context, $page->getSections(), $managedDialogs);

        $body = $this->pageFrameRenderer()->render($page, $renderedSections, null, null, $context);

        $this->appendManagedDialogs($body, $context, $managedDialogs);
        $this->appendPageRuntime($context);

        return $body;
    }

    private function appendThemeStylesheet(RenderContext $context): void
    {
        $assets = $context->document()->assets();

        try {
            $url = $this->runtimeAssetPublisher()->publishStyle('element-plus-admin-theme.css');
            if ($url !== null) {
                $assets->addStylesheet($url);

                return;
            }
        } catch (\Throwable) {
        }

        try {
            $css = RuntimeStyleLoader::load('element-plus-admin-theme.css');
        } catch (\Throwable) {
            $assets->addStylesheet(StaticResource::SC_V2_THEME_CSS);

            return;
        }

        $assets->addInlineStyle($css);
    }

    private function renderStandaloneForm(Form $form, RenderContext $context): AbstractHtmlElement
    {
        $state = $this->formRenderStateFactory()->createStandalone($form->key());

        $this->runtimePreparationCoordinator()->registerSimpleFormRuntime(
            $this->runtimeRegistry($context),
            $state,
            $form
        );

        return $this->renderForm($form, $state->model, $state->renderOptions, $context);
    }

    private function renderStandaloneTable(Table $table, RenderContext $context): AbstractHtmlElement
    {
        $state = $this->runtimePreparationCoordinator()->prepareTable(
            $this->runtimeRegistry($context),
            $table
        );
        return $this->tableBlockRenderer()->render($table, $state->bindings, $context);
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

    private function renderForm(
        Form $form,
        string $modelName,
        array|FormRenderOptions $options = [],
        ?RenderContext $context = null
    ): AbstractHtmlElement
    {
        return $this->formRenderer()->render(
            $form,
            $modelName,
            is_array($options) ? FormRenderOptions::fromArray($options) : $options,
            $context
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
        ?TableRenderBindings $tableBindings = null,
        ?RenderContext $context = null
    ): AbstractHtmlElement
    {
        return $this->actionButtonRenderer()->render($action, $rowScoped, $size, $tableBindings, $context);
    }

    private function renderStandaloneAction(Action $action, RenderContext $context): AbstractHtmlElement
    {
        return $this->renderActionButton($action, false, 'default', null, $context);
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

    private function createAppLoadingShell(): AbstractHtmlElement
    {
        return El::div()
            ->addClass('sc-v2-app-loading-shell')
            ->setAttr('aria-hidden', 'true')
            ->append(
                El::div()->addClass('sc-v2-app-loading')->append(
                    El::div()->addClass('sc-v2-app-loading__panel')->append(
                        El::div()->addClass('sc-v2-app-loading__spinner'),
                        El::div()->addClass('sc-v2-app-loading__title')->append('加载中')
                    )
                )
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

    private function runtimeAssetPublisher(): RuntimeAssetPublisher
    {
        return $this->runtimeAssetPublisher ??= new RuntimeAssetPublisher();
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
        PreparedListWidget $prepared,
        bool $wrapInSectionCards = false
    ): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-list');

        $filterForm = $prepared->filterForm;
        $filterState = $prepared->filterState;
        if ($filterForm !== null && $filterState !== null) {
            $filterContent = $this->renderForm($filterForm, $filterState->model, $filterState->renderOptions, $context);
            $body->append(
                $wrapInSectionCards
                    ? $this->wrapInSectionCard($filterContent)
                    : El::double('div')->addClass('sc-v2-list__filters')->append($filterContent)
            );
        }

        $table = $list->getTable();
        $tableState = $prepared->tableState;
        if ($table !== null && $tableState !== null) {
            $tableContent = $this->tableBlockRenderer()->render($table, $tableState->bindings, $context);
            $body->append(
                $wrapInSectionCards
                    ? $this->wrapInSectionCard($tableContent)
                    : $tableContent
            );
        }

        foreach ($prepared->dialogs as $dialog) {
            $body->append($this->renderManagedDialog($dialog, $context));
        }

        return $body;
    }

    private function wrapInSectionCard(AbstractHtmlElement $content, string $title = ''): AbstractHtmlElement
    {
        return $this->sectionCardFactory()->make($title)->append($content);
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
        return $this->resolveClassMappedMethod($component, self::COMPONENT_RENDERERS);
    }

    private function pageCompositionInspector(): PageCompositionInspector
    {
        return $this->pageCompositionInspector ??= new PageCompositionInspector();
    }

    private function pageFrameRenderer(): PageFrameRenderer
    {
        return $this->pageFrameRenderer ??= new PageFrameRenderer(
            $this->actionButtonRenderer(),
            $this->lightweightComponentRenderer()
        );
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

    private function tableBlockRenderer(): TableBlockRenderer
    {
        return $this->tableBlockRenderer ??= new TableBlockRenderer(
            $this->tableRenderer()
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
        return $this->formRenderer ??= new FormRenderer(
            $this->fieldRenderer(),
            new \Sc\Util\HtmlStructureV2\Support\FormTableColumnWalker(),
            $this->actionButtonRenderer(),
            $this->lightweightComponentRenderer()
        );
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
