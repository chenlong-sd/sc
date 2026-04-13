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

    private const BASE_CSS = <<<CSS
    [v-cloak]{display:none}
    html,body{height:100%}
    body{margin:0;background:#ffffff;color:#1f2937;font-family:"Helvetica Neue",Helvetica,"PingFang SC","Microsoft YaHei",sans-serif}
    #app{min-height:100%;box-sizing:border-box;padding:10px 10px 8px}
    .sc-v2-page{display:flex;flex-direction:column;gap:15px}
    .sc-v2-list{display:flex;flex-direction:column;gap:0}
    .sc-v2-table-block{display:flex;flex-direction:column;gap:10px}
    .sc-v2-list__filters{display:flex;flex-direction:column;gap:10px}
    .sc-v2-status-toggles{display:block}
    .sc-v2-status-toggle{margin-bottom:10px;box-sizing:border-box;max-width:100%}
    .sc-v2-status-toggles--inline .sc-v2-status-toggle{display:inline-flex;vertical-align:top;margin-right:10px}
    .sc-v2-status-toggles--inline .sc-v2-status-toggle:last-child{margin-right:0}
    .sc-v2-status-toggles--newline .sc-v2-status-toggle{display:flex;width:100%;margin-right:0}
    .sc-v2-status-toggle--plain{align-items:center}
    .sc-v2-status-toggle--labeled{position:relative;align-items:center;min-height:35px;padding:0 10px;border-radius:5px;box-shadow:0 0 5px #bbb;background:#fff;overflow:hidden}
    .sc-v2-status-toggle--labeled::before{content:'';position:absolute;left:0;top:0;bottom:0;width:110px;background:rgb(121, 187, 255);border-radius:5px 0 0 5px}
    .sc-v2-status-toggle__label{position:relative;z-index:1;display:inline-flex;align-items:center;color:#fff;margin-right:10px}
    .sc-v2-status-toggle__label-text{display:inline-block;width:80px;font-size:14px;font-weight:700;text-align:justify;text-align-last:justify;white-space:nowrap}
    .sc-v2-status-toggle__label-colon{display:inline-block;font-size:14px;font-weight:700}
    .sc-v2-status-toggle__buttons{position:relative;z-index:1;display:flex;flex-wrap:wrap;margin-right:10px}
    .sc-v2-status-toggle--plain .sc-v2-status-toggle__buttons{margin-right:0}
    .sc-v2-page__header{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;flex-wrap:wrap;margin-bottom:3px}
    .sc-v2-page__title{display:flex;flex-direction:column;gap:6px}
    .sc-v2-page__title h1{margin:0;font-size:28px;line-height:1.2;color:#111827}
    .sc-v2-page__title p{margin:0;color:#6b7280;font-size:14px}
    .sc-v2-actions{display:flex;gap:10px;flex-wrap:wrap}
    .sc-v2-section .el-card__body{display:flex;flex-direction:column;gap:16px}
    .sc-v2-section__header{display:flex;justify-content:space-between;align-items:center;gap:12px;font-weight:600}
    .sc-v2-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .sc-v2-toolbar__actions{display:flex;gap:6px;flex-wrap:wrap}
    .sc-v2-toolbar__tools{display:flex;gap:6px;flex-wrap:wrap;margin-left:auto}
    .sc-v2-toolbar__actions .el-button+.el-button,.sc-v2-toolbar__tools .el-button+.el-button{margin-left:0}
    .sc-v2-form__control{display:flex;align-items:flex-start;gap:12px;width:100%}
    .sc-v2-form__control>:first-child{flex:1 1 auto;min-width:0}
    .sc-v2-form__footer{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding-top:6px}
    .sc-v2-form__footer .el-button+.el-button{margin-left:0}
    .sc-v2-form__suffix{display:flex;align-items:center;gap:8px;flex:0 0 auto;flex-wrap:wrap}
    .sc-v2-form__suffix-text{font-size:13px;line-height:1.5;color:#6b7280}
    .sc-v2-form__help{margin-top:6px;font-size:12px;line-height:1.5;color:#909399}
    .sc-v2-picker{display:flex;flex-direction:column;gap:10px;width:100%}
    .sc-v2-picker__panel{display:flex;flex-direction:column;gap:8px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#fcfcfd}
    .sc-v2-picker__summary{font-size:12px;line-height:18px;color:#6b7280}
    .sc-v2-picker__empty{padding:10px 12px;border:1px dashed #dcdfe6;border-radius:6px;background:#fafafa;font-size:13px;line-height:20px;color:#909399}
    .sc-v2-picker__list{display:flex;flex-direction:column;gap:8px;max-height:180px;overflow:auto}
    .sc-v2-picker__item{display:flex;align-items:center;gap:12px;min-height:38px;padding:0 10px;border:1px solid #ebeef5;border-radius:6px;background:#ffffff}
    .sc-v2-picker__item-text{flex:1 1 auto;min-width:0;font-size:13px;line-height:20px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sc-v2-picker__actions{display:flex;gap:10px;flex-wrap:wrap}
    .sc-v2-icon-selector-panel{display:flex;flex-direction:column;gap:10px}
    .sc-v2-icon-selector-group{display:flex;flex-direction:column;gap:14px}
    .sc-v2-icon-selector-group__section{display:flex;flex-direction:column;gap:8px}
    .sc-v2-icon-selector-group__section.has-divider{padding-top:14px;border-top:1px solid #ebeef5}
    .sc-v2-icon-selector-group__title{font-size:12px;line-height:1.4;color:#909399}
    .sc-v2-icon-selector{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:6px}
    .sc-v2-icon-selector__item{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:68px;padding:8px 6px;border:1px solid #ebeef5;border-radius:6px;background:#fff;cursor:pointer;transition:border-color .2s ease,background-color .2s ease,box-shadow .2s ease,transform .2s ease}
    .sc-v2-icon-selector__item:hover{border-color:#409eff;background:#f5f9ff;transform:translateY(-1px)}
    .sc-v2-icon-selector__item.is-active{border-color:#409eff;background:#ecf5ff;box-shadow:0 0 0 1px rgba(64,158,255,.15) inset}
    .sc-v2-icon-selector__item.is-unmatched{opacity:.78}
    .sc-v2-icon-selector__preview{font-size:18px;line-height:1;color:#111827}
    .sc-v2-icon-selector__label{width:100%;font-size:11px;line-height:1.35;color:#6b7280;text-align:center;word-break:break-all}
    .sc-v2-icon-selector__empty{padding:18px 12px;color:#909399;text-align:center;grid-column:1/-1}
    .sc-v2-rich-editor{width:100%}
    .sc-v2-rich-editor__mount{width:100%}
    .sc-v2-rich-editor .sre-root{width:100%}
    .sc-v2-row-actions{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
    .sc-v2-row-actions .sc-v2-table-drag-handle{cursor:move;touch-action:none}
    .sc-v2-row-actions .sc-v2-table-drag-handle .el-icon{cursor:move}
    .sc-v2-event-column .cell{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:center}
    .sc-v2-event-column .el-button+.el-button{margin-left:0}
    .sc-v2-filters__actions{display:flex;gap:10px;flex-wrap:wrap}
    .sc-v2-table__images{display:flex;gap:8px;flex-wrap:wrap}
    .sc-v2-table-settings-dialog{max-height:92vh;display:flex;flex-direction:column}
    .sc-v2-table-settings-dialog .el-dialog__body{padding-top:16px;padding-bottom:16px;overflow:hidden}
    .sc-v2-table-settings-dialog .el-dialog__footer{padding-top:0}
    .sc-v2-table-settings{display:flex;flex-direction:column;gap:16px;min-height:0}
    .sc-v2-table-settings .el-tabs{display:flex;flex-direction:column;min-height:0}
    .sc-v2-table-settings .el-tabs__content{min-height:0}
    .sc-v2-table-settings__pane{display:flex;flex-direction:column;gap:16px;min-height:0}
    .sc-v2-table-settings__switches{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-table-settings__switch{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid var(--el-border-color-light);border-radius:8px;min-width:180px}
    .sc-v2-table-settings-drag-handle{cursor:move;touch-action:none}
    .sc-v2-table-settings-drag-handle .el-icon{cursor:move}
    .sc-v2-table-settings__footer{display:flex;justify-content:flex-end}
    .sc-v2-table-settings__footer-actions{display:flex;gap:10px;flex-wrap:wrap}
    .sc-v2-stack{display:flex;flex-direction:column}
    .sc-v2-grid{display:grid}
    .sc-v2-block-title{display:flex;flex-direction:column;gap:6px}
    .sc-v2-block-title h2{margin:0;font-size:22px;line-height:1.25;color:#111827}
    .sc-v2-block-title p{margin:0;color:#6b7280;font-size:14px}
    .sc-v2-block-text{margin:0;line-height:1.7;color:#374151}
    .sc-v2-block-text--muted{color:#6b7280}
    .sc-v2-form-section-block:not(:last-child){margin-bottom:18px}
    .sc-v2-form-section-block--plain{padding:4px 0}
    .sc-v2-form-section-block--plain:not(:last-child){padding-bottom:20px;border-bottom:1px dashed #e5e7eb}
    .sc-v2-form-section-card{border:1px solid #e7edf5;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);box-shadow:0 10px 28px rgba(15,23,42,.05),0 2px 6px rgba(15,23,42,.04);overflow:hidden}
    .sc-v2-form-section-card>.el-card__body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:18px}
    .sc-v2-form-section{display:flex;flex-direction:column;gap:18px}
    .sc-v2-form-section__header{display:flex;flex-direction:column;gap:4px;padding-bottom:14px;border-bottom:1px solid #eef2f7}
    .sc-v2-form-section__heading{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
    .sc-v2-form-section__heading-body{position:relative;display:flex;flex-direction:column;gap:4px;min-width:0;padding-left:14px}
    .sc-v2-form-section__heading-body::before{content:'';position:absolute;left:0;top:2px;bottom:2px;width:4px;border-radius:999px;background:linear-gradient(180deg,#409eff 0%,#79bbff 100%)}
    .sc-v2-form-section__actions{display:flex;gap:8px;flex-wrap:wrap;flex:0 0 auto;padding-left:12px}
    .sc-v2-form-section__header h3{margin:0;font-size:17px;font-weight:700;line-height:1.35;color:#111827}
    .sc-v2-form-section__header p{margin:0;max-width:860px;color:#6b7280;font-size:13px;line-height:1.65}
    .sc-v2-form-section__body{display:flex;flex-direction:column;gap:4px}
    .sc-v2-form-inline{display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .sc-v2-form-tabs{display:flex;flex-direction:column;gap:8px}
    .sc-v2-form-tabs .el-tabs__content{overflow:visible}
    .sc-v2-form-tabs__pane{padding-top:8px}
    .sc-v2-form-collapse{display:flex;flex-direction:column;gap:8px}
    .sc-v2-form-collapse__item-body{padding-top:8px}
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
    .sc-v2-form-table__actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:center}
    .sc-v2-form-table__actions .sc-v2-table-drag-handle{cursor:move;touch-action:none}
    .sc-v2-form-table__actions .sc-v2-table-drag-handle .el-icon{cursor:move}
    .sc-v2-form-table__item{margin-bottom:0}
    @media (max-width: 768px){
      #app{padding:8px 8px 6px}
      .sc-v2-page__header{flex-direction:column;align-items:stretch}
      .sc-v2-actions,.sc-v2-toolbar,.sc-v2-toolbar__actions,.sc-v2-filters__actions,.sc-v2-status-toggles{width:100%}
      .sc-v2-form__control{flex-direction:column;align-items:stretch}
      .sc-v2-form__footer{justify-content:flex-start}
      .sc-v2-form__suffix{width:100%}
      .sc-v2-form-section-block:not(:last-child){margin-bottom:14px}
      .sc-v2-form-section-card>.el-card__body{padding:16px 14px 16px}
      .sc-v2-form-section__heading{flex-direction:column;align-items:stretch}
      .sc-v2-form-section__actions{width:100%;padding-left:0}
      .sc-v2-status-toggles--inline .sc-v2-status-toggle{display:flex;margin-right:0}
      .sc-v2-status-toggle{align-items:stretch}
      .sc-v2-status-toggle--labeled{padding:8px 10px}
      .sc-v2-status-toggle--labeled::before{width:96px}
      .sc-v2-status-toggle__label{min-height:32px}
      .sc-v2-status-toggle__label-text{width:66px}
      .sc-v2-status-toggle__buttons{width:100%}
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
        $assets->addScript(StaticResource::SORTABLE);
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
