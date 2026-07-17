<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CollapseItemNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CollapseNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormObjectGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormTable;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\GridNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\InlineNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\SectionNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\TabPaneNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\TabsNode;
use Sc\Util\HtmlStructureV2\Contracts\ConditionalFormNode;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\FormPath;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnSchema;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnWalker;
use Sc\Util\HtmlStructureV2\Support\ResolvesClassMappedMethod;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\AppliesRenderableAttributes;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;
use RuntimeException;

final class FormRenderer
{
    use AppliesRenderableAttributes;
    use BuildsJsExpressions;
    use EncodesJsValues;
    use ResolvesClassMappedMethod;
    private const DRAG_SORT_HANDLE_CLASS = 'sc-v2-table-drag-handle';

    private const NODE_RENDERERS = [
        Field::class => 'renderFieldNode',
        SectionNode::class => 'renderSectionNode',
        InlineNode::class => 'renderInlineNode',
        GridNode::class => 'renderGridNode',
        TabsNode::class => 'renderTabsNode',
        TabPaneNode::class => 'renderUnsupportedTabPaneNode',
        CollapseNode::class => 'renderCollapseNode',
        CollapseItemNode::class => 'renderUnsupportedCollapseItemNode',
        FormObjectGroup::class => 'renderObjectGroup',
        FormTable::class => 'renderFormTable',
        FormArrayGroup::class => 'renderArrayGroup',
        CustomNode::class => 'renderCustomNode',
    ];

    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
        private readonly FormTableColumnWalker $formTableColumnWalker = new FormTableColumnWalker(),
        private readonly ActionButtonRenderer $actionButtonRenderer = new ActionButtonRenderer(),
        private readonly ?LightweightComponentRenderer $lightweightComponentRenderer = null,
    ) {
    }

    public function render(
        Form $form,
        string $modelName,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null
    ): AbstractHtmlElement
    {
        $renderOptions = $options->withShowLabels($form->shouldShowLabels());
        $attrs = [
            ':model' => $modelName,
            'label-width' => $form->shouldShowLabels() ? $form->getLabelWidth() : '0',
        ];

        if ($renderOptions->ref !== null) {
            $attrs['ref'] = $renderOptions->ref;
        }
        if ($renderOptions->rules !== null) {
            $attrs[':rules'] = $renderOptions->rules;
        }

        $hasFooterActions = !$renderOptions->isFilterMode() && $form->getFooterActions() !== [];
        $element = El::double('el-form')->setAttrs($attrs)->addClass('sc-v2-form');
        if ($hasFooterActions) {
            $element->addClass('sc-v2-form--has-footer-actions');
        }
        $context = new FormNodeRenderContext(
            modelName: $modelName,
            inline: $form->isInline(),
            formReadonly: $form->isReadonly(),
            options: $renderOptions,
            renderContext: $renderContext,
        );

        if ($form->isInline()) {
            $element->setAttr(':inline', 'true');
            $this->appendRenderedChildren($element, $form->children(), $context);
        } else {
            $row = El::double('el-row')->setAttr(':gutter', 16);
            $this->appendRenderedChildren($row, $form->children(), $context->withInline(false));
            $element->append($row);
        }

        if ($hasFooterActions) {
            $element->append($this->renderFooterActions($form, $context));
        }

        if ($renderOptions->isFilterMode() && !$form->isReadonly()) {
            $element->append($this->renderFilterActions($form, $renderOptions));
        }

        return $element;
    }

    private function renderNode(FormNode $node, FormNodeRenderContext $context): AbstractHtmlElement
    {
        if ($node instanceof ConditionalFormNode && !$node->isVisible()) {
            return El::fictitious();
        }

        $method = $this->resolveNodeRendererMethod($node);
        if ($method === null) {
            throw new InvalidArgumentException('Unsupported V2 form node: ' . $node::class);
        }

        $rendered = $this->{$method}($node, $context);
        if (!$node instanceof Field) {
            $visibleWhen = $this->buildNodeExpression(
                $node instanceof ConditionalFormNode ? $node->getVisibleWhen() : null,
                $context,
                $this->nodePathExpression($node, $context),
                $this->buildNodeExpressionMeta($node)
            );

            if ($visibleWhen !== null) {
                if ($node instanceof FormObjectGroup || $node->getAfterSpan() > 0) {
                    return El::double('template')->setAttr('v-if', $visibleWhen)->append($rendered);
                }

                $rendered->setAttr('v-if', $visibleWhen);
            }
        }

        return $rendered;
    }

    private function renderFieldNode(Field $field, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $fieldPath = $context->fieldPath($field->name());

        if (!$context->isArrayRow()) {
            return $this->fieldRenderer->render(
                $field,
                $context->modelName,
                $fieldPath,
                $context->inline,
                $context->options,
                $context->renderContext,
                $context->formReadonly,
                $context->labelWidth,
                $context->disabledWhen,
                $context->readonlyWhen
            );
        }

        return $this->fieldRenderer->renderScoped(
            $field,
            $context->modelName,
            $this->arrayFieldPropExpression($context, $fieldPath),
            $fieldPath,
            $context->inline,
            $context->options,
            $context->renderContext,
            $context->formReadonly,
            $context->labelWidth,
            $context->disabledWhen,
            $context->readonlyWhen
        );
    }

    private function renderSectionNode(SectionNode $section, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $sectionContext = $this->contextForConditionalNode($context, $section)
            ->mergeReadonly($section->isReadonly())
            ->inheritLabelWidth($section->getLabelWidth());
        $body = El::double('div')->addClass('sc-v2-form-section');
        $header = $this->renderSectionHeader($section, $context->renderContext);
        if ($header !== null) {
            $body->append($header);
        }
        $body->append(
            El::double('div')
                ->addClass('sc-v2-form-section__body')
                ->append($this->renderChildrenRow($section->getChildren(), $sectionContext))
        );

        $wrapped = $section->isPlain()
            ? $body
            : El::double('el-card')->addClass('sc-v2-form-section-card')->append($body);
        $wrapped = $this->applyRenderableAttributes($wrapped, $section->getRenderAttributes());

        $block = $this->makeBlockNode($wrapped, $section)
            ->addClass('sc-v2-form-section-block');

        if ($section->isPlain()) {
            $block->addClass('sc-v2-form-section-block--plain');
        }

        return $this->appendAfterSpanColumn($block, $section->getAfterSpan());
    }

    private function renderInlineNode(InlineNode $inlineNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $inlineContext = $this->contextForConditionalNode($context, $inlineNode)
            ->mergeReadonly($inlineNode->isReadonly())
            ->inheritLabelWidth($inlineNode->getLabelWidth());
        $body = $this->applyRenderableAttributes(
            El::double('div')->addClass('sc-v2-form-inline'),
            $inlineNode->getRenderAttributes()
        );
        $this->appendRenderedChildren($body, $inlineNode->getChildren(), $inlineContext->withInline(true));

        return $this->wrapBlockNode($body, $inlineNode);
    }

    private function renderGridNode(GridNode $gridNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $gridContext = $this->contextForConditionalNode($context, $gridNode)
            ->mergeReadonly($gridNode->isReadonly())
            ->inheritLabelWidth($gridNode->getLabelWidth());
        $row = $this->applyRenderableAttributes(
            El::double('el-row')->setAttr(':gutter', $gridNode->getGutter()),
            $gridNode->getRenderAttributes()
        );
        $this->appendRenderedChildren($row, $gridNode->getChildren(), $gridContext->withInline(false));

        return $this->wrapBlockNode($row, $gridNode);
    }

    private function renderTabsNode(TabsNode $tabsNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $tabsContext = $this->contextForConditionalNode($context, $tabsNode)
            ->mergeReadonly($tabsNode->isReadonly())
            ->inheritLabelWidth($tabsNode->getLabelWidth());
        $tabs = $this->applyRenderableAttributes(
            El::double('el-tabs')->addClass('sc-v2-form-tabs'),
            $tabsNode->getRenderAttributes()
        );

        if ($tabsNode->getType() !== '') {
            $tabs->setAttr('type', $tabsNode->getType());
        }
        if ($tabsNode->getTabPosition() !== 'top') {
            $tabs->setAttr('tab-position', $tabsNode->getTabPosition());
        }
        if ($tabsNode->isStretch()) {
            $tabs->setAttr('stretch', '');
        }

        foreach ($tabsNode->getTabs() as $index => $tab) {
            if (!$tab->isVisible()) {
                continue;
            }

            $tabContext = $this->contextForConditionalNode($tabsContext, $tab)
                ->mergeReadonly($tab->isReadonly())
                ->inheritLabelWidth($tab->getLabelWidth());
            $pane = El::double('el-tab-pane')->setAttrs([
                'label' => $tab->label(),
                'name' => (string) $index,
            ]);
            $pane = $this->applyRenderableAttributes($pane, $tab->getRenderAttributes());
            $visibleWhen = $this->buildNodeExpression(
                $tab->getVisibleWhen(),
                $tabsContext,
                $this->nodePathExpression($tab, $tabsContext),
                $this->buildNodeExpressionMeta($tab)
            );
            if ($visibleWhen !== null) {
                $pane->setAttr('v-if', $visibleWhen);
            }
            if ($tab->isLazy()) {
                $pane->setAttr('lazy', '');
            }

            $pane->append($this->renderChildrenBodyContainer('sc-v2-form-tabs__pane', $tab->getChildren(), $tabContext));
            $tabs->append($pane);
        }

        return $this->wrapBlockNode($tabs, $tabsNode);
    }

    private function renderCollapseNode(CollapseNode $collapseNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $collapseContext = $this->contextForConditionalNode($context, $collapseNode)
            ->mergeReadonly($collapseNode->isReadonly())
            ->inheritLabelWidth($collapseNode->getLabelWidth());
        $collapse = $this->applyRenderableAttributes(
            El::double('el-collapse')->addClass('sc-v2-form-collapse'),
            $collapseNode->getRenderAttributes()
        );
        if ($collapseNode->isAccordion()) {
            $collapse->setAttr('accordion', '');
        }

        foreach ($collapseNode->getItems() as $index => $item) {
            if (!$item->isVisible()) {
                continue;
            }

            $itemContext = $this->contextForConditionalNode($collapseContext, $item)
                ->mergeReadonly($item->isReadonly())
                ->inheritLabelWidth($item->getLabelWidth());
            $itemElement = El::double('el-collapse-item')->setAttrs([
                    'title' => $item->title(),
                    'name' => (string) $index,
                ]);
            $itemElement = $this->applyRenderableAttributes($itemElement, $item->getRenderAttributes());
            $visibleWhen = $this->buildNodeExpression(
                $item->getVisibleWhen(),
                $collapseContext,
                $this->nodePathExpression($item, $collapseContext),
                $this->buildNodeExpressionMeta($item)
            );
            if ($visibleWhen !== null) {
                $itemElement->setAttr('v-if', $visibleWhen);
            }

            $collapse->append(
                $itemElement->append($this->renderChildrenBodyContainer(
                    'sc-v2-form-collapse__item-body',
                    $item->getChildren(),
                    $itemContext
                ))
            );
        }

        return $this->wrapBlockNode($collapse, $collapseNode);
    }

    private function renderUnsupportedTabPaneNode(TabPaneNode $tabPaneNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        throw new InvalidArgumentException(sprintf(
            'Forms::tab("%s") can only be used inside Forms::tabs().',
            $tabPaneNode->label()
        ));
    }

    private function renderUnsupportedCollapseItemNode(
        CollapseItemNode $collapseItemNode,
        FormNodeRenderContext $context
    ): AbstractHtmlElement {
        throw new InvalidArgumentException(sprintf(
            'Forms::collapseItem("%s") can only be used inside Forms::collapse().',
            $collapseItemNode->title()
        ));
    }

    private function renderObjectGroup(FormObjectGroup $group, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $groupContext = $this->contextForConditionalNode($context, $group)
            ->mergeReadonly($group->isReadonly())
            ->inheritLabelWidth($group->getLabelWidth());
        $content = El::fictitious();
        $nextPrefix = FormPath::resolve($groupContext->pathPrefix, $group->name());
        $nextModelName = $this->jsModelAccessor($groupContext->modelName, $group->name());
        $this->appendRenderedChildren(
            $content,
            $group->getChildren(),
            $groupContext->nested($nextModelName, $nextPrefix)
        );

        return $content;
    }

    private function renderArrayGroup(FormArrayGroup $group, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $groupContext = $this->contextForConditionalNode($context, $group)
            ->mergeReadonly($group->isReadonly())
            ->inheritLabelWidth($group->getLabelWidth());
        $arrayPath = FormPath::resolve($groupContext->pathPrefix, $group->name());
        $scopeLiteral = $this->jsLiteral($groupContext->options->remoteScope ?? $groupContext->options->uploadScope ?? 'form');
        $arrayPathExpression = $this->resolveArrayPathExpression($groupContext, $arrayPath);
        $tableScopeVariable = $this->tableLoopVariable($groupContext);
        [$rowVariable, $rowIndexVariable] = $this->arrayLoopVariables($groupContext);

        $body = $this->applyRenderableAttributes(
            El::double('div')->addClass('sc-v2-form-array'),
            $group->getRenderAttributes()
        );
        $header = $this->renderBlockTitleHeader($group->getTitle(), 'sc-v2-form-array__header');
        if ($header !== null) {
            $body->append($header);
        }

        $rows = El::double('div')->addClass('sc-v2-form-array__rows');
        $template = El::double('template')->setAttrs([
            'v-for' => sprintf(
                '(%s, %s) in getFormArrayRows(%s, %s)',
                $rowVariable,
                $rowIndexVariable,
                $scopeLiteral,
                $arrayPathExpression
            ),
            ':key' => sprintf('%s.__sc_v2_row_key ?? %s', $rowVariable, $rowIndexVariable),
        ]);
        $card = El::double('el-card')->addClass('sc-v2-form-array__item');
        $card->append($this->renderArrayGroupRowHeader(
            $group,
            $scopeLiteral,
            $arrayPathExpression,
            $rowIndexVariable,
            $groupContext->formReadonly,
            $this->interactionDisabledExpression($groupContext)
        ));
        $card->append($this->renderArrayGroupRowBody(
            $group,
            $groupContext,
            $arrayPath,
            $arrayPathExpression,
            $rowVariable,
            $rowIndexVariable
        ));
        $template->append($card);
        $rows->append($template);
        $body->append($rows);

        if (!$groupContext->formReadonly && $group->isAddable()) {
            $body->append($this->renderAddRowFooter(
                'sc-v2-form-array__footer',
                $group->getAddButtonText(),
                sprintf('addFormArrayRow(%s, %s)', $scopeLiteral, $arrayPathExpression),
                $this->interactionDisabledExpression($groupContext)
            ));
        }

        if ($groupContext->inline) {
            return $body;
        }

        return $this->wrapBlockNode($body, $group);
    }

    private function renderFormTable(FormTable $table, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $tableContext = $this->contextForConditionalNode($context, $table)
            ->mergeReadonly($table->isReadonly())
            ->inheritLabelWidth($table->getLabelWidth());
        $columns = $this->formTableColumnWalker->build($table->getChildren(), $table->name());

        $arrayPath = FormPath::resolve($tableContext->pathPrefix, $table->name());
        $scopeLiteral = $this->jsLiteral($tableContext->options->remoteScope ?? $tableContext->options->uploadScope ?? 'form');
        $arrayPathExpression = $this->resolveArrayPathExpression($tableContext, $arrayPath);
        $tableScopeVariable = $this->tableLoopVariable($tableContext);

        $body = El::double('div')->addClass('sc-v2-form-table');
        $header = $this->renderBlockTitleHeader($table->getTitle(), 'sc-v2-form-table__header');
        if ($header !== null) {
            $body->append($header);
        }

        $tableElement = El::double('el-table')->setAttrs([
            ':data' => sprintf('getFormArrayRows(%s, %s)', $scopeLiteral, $arrayPathExpression),
            ':border' => $table->useBorder() ? 'true' : 'false',
            'row-key' => '__sc_v2_row_key',
            'empty-text' => $table->getEmptyText(),
            'class' => 'sc-v2-form-table__table',
            'data-sc-form-table' => '1',
            'data-sc-form-table-sortable' => !$tableContext->formReadonly && $table->isReorderable() ? '1' : '0',
            ':data-sc-form-scope' => $scopeLiteral,
            ':data-sc-form-array-path' => $arrayPathExpression,
        ]);
        $tableElement = $this->applyRenderableAttributes($tableElement, $table->getRenderAttributes());

        foreach ($columns as $columnSchema) {
            $tableElement->append(
                $this->renderFormTableColumnNode(
                    $columnSchema,
                    $arrayPathExpression,
                    $tableContext->options,
                    $tableContext->renderContext,
                    $tableContext->formReadonly,
                    $tableContext->arrayDepth,
                    $tableScopeVariable,
                    $tableContext->disabledWhen,
                    $tableContext->readonlyWhen
                )
            );
        }

        if (!$tableContext->formReadonly && ($table->isReorderable() || $table->isRemovable())) {
            $tableElement->append($this->renderFormTableActionColumn(
                $table,
                $scopeLiteral,
                $arrayPathExpression,
                $tableScopeVariable,
                $this->tableRowIndexExpression($tableScopeVariable),
                $this->interactionDisabledExpression($tableContext)
            ));
        }

        $body->append($tableElement);

        if (!$tableContext->formReadonly && $table->isAddable()) {
            $body->append($this->renderAddRowFooter(
                'sc-v2-form-table__footer',
                $table->getAddButtonText(),
                sprintf('addFormArrayRow(%s, %s)', $scopeLiteral, $arrayPathExpression),
                $this->interactionDisabledExpression($tableContext)
            ));
        }

        if ($tableContext->inline) {
            return $body;
        }

        return $this->wrapBlockNode($body, $table);
    }

    private function renderFormTableColumnNode(
        FormTableColumnSchema $columnSchema,
        string $arrayPathExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        bool $formReadonly = false,
        int $tableArrayDepth = 0,
        string $tableScopeVariable = 'scope',
        ?string $inheritedDisabledWhen = null,
        ?string $inheritedReadonlyWhen = null
    ): AbstractHtmlElement {
        $column = El::double('el-table-column');
        if ($columnSchema->label() !== '') {
            $column->setAttr('label', $columnSchema->label());
        }
        foreach ($columnSchema->columnAttributes() as $attribute => $value) {
            if (!is_string($attribute) || trim($attribute) === '' || $value === null || $value === false) {
                continue;
            }

            $column->setAttr($attribute, $value === true ? '' : $value);
        }

        if ($columnSchema->isGroup()) {
            foreach ($columnSchema->children() as $childColumn) {
                $column->append($this->renderFormTableColumnNode(
                    $childColumn,
                    $arrayPathExpression,
                    $options,
                    $renderContext,
                    $formReadonly,
                    $tableArrayDepth,
                    $tableScopeVariable,
                    $inheritedDisabledWhen,
                    $inheritedReadonlyWhen
                ));
            }

            return $column;
        }

        $tableRowModelExpression = $this->tableRowModelExpression($tableScopeVariable);
        $tableRowIndexExpression = $this->tableRowIndexExpression($tableScopeVariable);
        $template = El::double('template')->setAttr('#default', $tableScopeVariable);

        $aliasedContent = $this->renderFormTableScopedContent($tableScopeVariable);

        if ($columnSchema->isField()) {
            $field = $columnSchema->field();
            if ($field === null || !$field->isVisible() || $field->type() === FieldType::HIDDEN) {
                return El::fictitious();
            }

            $fieldModelName = $columnSchema->modelPath() === ''
                ? $tableRowModelExpression
                : $this->jsModelAccessor($tableRowModelExpression, $columnSchema->modelPath());

            $propExpression = sprintf(
                'joinFormArrayFieldPath(%s, %s, %s)',
                $arrayPathExpression,
                $tableRowIndexExpression,
                $this->jsLiteral($columnSchema->path())
            );
            $aliasedContent->append(
                $this->fieldRenderer->renderTableCell(
                    $field,
                    $fieldModelName,
                    $columnSchema->path(),
                    $propExpression,
                    $options,
                    $renderContext,
                    $formReadonly || $columnSchema->isReadonly(),
                    $inheritedDisabledWhen,
                    $inheritedReadonlyWhen
                )
            );
        } elseif ($columnSchema->arrayGroup() !== null) {
            $aliasedContent->append($this->renderFormTableArrayColumn(
                $columnSchema,
                $arrayPathExpression,
                $options,
                $renderContext,
                $formReadonly || $columnSchema->isReadonly(),
                $tableArrayDepth,
                $tableScopeVariable,
                $inheritedDisabledWhen,
                $inheritedReadonlyWhen
            ));
        } else {
            $customNode = $columnSchema->customNode();
            if ($customNode === null) {
                return El::fictitious();
            }

            $aliasedContent->append($this->renderNode(
                $customNode,
                new FormNodeRenderContext(
                    modelName: $tableRowModelExpression,
                    inline: true,
                    formReadonly: $formReadonly,
                    options: $options,
                    renderContext: $renderContext,
                    disabledWhen: $inheritedDisabledWhen,
                    readonlyWhen: $inheritedReadonlyWhen
                )
            ));
        }

        $template->append($aliasedContent);
        $column->append($template);

        return $column;
    }

    private function renderFormTableArrayColumn(
        FormTableColumnSchema $columnSchema,
        string $arrayPathExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        bool $formReadonly = false,
        int $tableArrayDepth = 0,
        string $tableScopeVariable = 'scope',
        ?string $inheritedDisabledWhen = null,
        ?string $inheritedReadonlyWhen = null
    ): AbstractHtmlElement {
        $group = $columnSchema->arrayGroup();
        if ($group === null) {
            return El::fictitious();
        }

        $tableRowModelExpression = $this->tableRowModelExpression($tableScopeVariable);
        $tableRowIndexExpression = $this->tableRowIndexExpression($tableScopeVariable);
        $rowContext = new FormNodeRenderContext(
            modelName: $tableRowModelExpression,
            inline: true,
            formReadonly: $formReadonly,
            options: $options,
            renderContext: $renderContext,
            arrayPath: '__table_row__',
            arrayPathExpression: $arrayPathExpression,
            rowIndexExpression: $tableRowIndexExpression,
            arrayDepth: $tableArrayDepth + 1,
            disabledWhen: $inheritedDisabledWhen,
            readonlyWhen: $inheritedReadonlyWhen,
        );

        return $this->renderNode($group, $rowContext);
    }

    private function renderFormTableActionColumn(
        FormTable $table,
        string $scopeLiteral,
        string $arrayPathExpression,
        string $tableScopeVariable,
        string $rowIndexExpression,
        ?string $disabledExpression = null
    ): AbstractHtmlElement {
        $column = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'align' => 'center',
            'width' => $table->isReorderable() && $table->isRemovable() ? '132' : '80',
        ]);

        $template = El::double('template')->setAttr('#default', $tableScopeVariable);
        $actions = El::double('div')->addClass('sc-v2-form-table__actions');

        if ($table->isReorderable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'size' => 'small',
                    ':disabled' => sprintf(
                        '%s',
                        $this->resolveBooleanStateExpression(
                            false,
                            sprintf('getFormArrayRows(%s, %s).length <= 1', $scopeLiteral, $arrayPathExpression),
                            $disabledExpression
                        ) ?? 'false'
                    ),
                    'class' => self::DRAG_SORT_HANDLE_CLASS,
                    'icon' => 'Rank',
                ])->append('拖动')
            );
        }

        if ($table->isRemovable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'danger',
                    'size' => 'small',
                    ':disabled' => $disabledExpression ?? 'false',
                    '@click' => sprintf(
                        'removeFormArrayRow(%s, %s, %s)',
                        $scopeLiteral,
                        $arrayPathExpression,
                        $rowIndexExpression
                    ),
                ])->append('删除')
            );
        }

        $template->append($actions);
        $column->append($template);

        return $column;
    }

    private function renderArrayGroupRowHeader(
        FormArrayGroup $group,
        string $scopeLiteral,
        string $arrayPathExpression,
        string $rowIndexVariable,
        bool $formReadonly = false,
        ?string $disabledExpression = null
    ): AbstractHtmlElement {
        $header = El::double('template')->setAttr('#header');
        $container = El::double('div')->addClass('sc-v2-form-array__item-header');
        $container->append(
            El::double('span')->addClass('sc-v2-form-array__item-title')->append(
                sprintf('第 {{ %s + 1 }} 组', $rowIndexVariable)
            )
        );

        if (!$formReadonly && ($group->isReorderable() || $group->isRemovable())) {
            $actions = El::double('div')->addClass('sc-v2-form-array__item-actions');

            if ($group->isReorderable()) {
                $actions->append(
                    El::double('el-button')->setAttrs([
                        'link' => '',
                        'type' => 'primary',
                        'size' => 'small',
                        ':disabled' => $this->resolveBooleanStateExpression(
                            false,
                            sprintf('%s === 0', $rowIndexVariable),
                            $disabledExpression
                        ) ?? 'false',
                        '@click' => sprintf(
                            'moveFormArrayRow(%s, %s, %s, "up")',
                            $scopeLiteral,
                            $arrayPathExpression,
                            $rowIndexVariable
                        ),
                    ])->append('上移'),
                    El::double('el-button')->setAttrs([
                        'link' => '',
                        'type' => 'primary',
                        'size' => 'small',
                        ':disabled' => sprintf(
                            '%s',
                            $this->resolveBooleanStateExpression(
                                false,
                                sprintf(
                                    '%s >= getFormArrayRows(%s, %s).length - 1',
                                    $rowIndexVariable,
                                    $scopeLiteral,
                                    $arrayPathExpression
                                ),
                                $disabledExpression
                            ) ?? 'false'
                        ),
                        '@click' => sprintf(
                            'moveFormArrayRow(%s, %s, %s, "down")',
                            $scopeLiteral,
                            $arrayPathExpression,
                            $rowIndexVariable
                        ),
                    ])->append('下移')
                );
            }

            if ($group->isRemovable()) {
                $actions->append(
                    El::double('el-button')->setAttrs([
                        'link' => '',
                        'type' => 'danger',
                        'size' => 'small',
                        ':disabled' => $disabledExpression ?? 'false',
                        '@click' => sprintf(
                            'removeFormArrayRow(%s, %s, %s)',
                            $scopeLiteral,
                            $arrayPathExpression,
                            $rowIndexVariable
                        ),
                    ])->append('删除')
                );
            }

            $container->append($actions);
        }

        $header->append($container);

        return $header;
    }

    private function renderArrayGroupRowBody(
        FormArrayGroup $group,
        FormNodeRenderContext $context,
        string $arrayPath,
        string $arrayPathExpression,
        string $rowVariable,
        string $rowIndexVariable
    ): AbstractHtmlElement {
        return $this->renderChildrenBodyContainer(
            'sc-v2-form-array__item-body',
            $group->getChildren(),
            $context->forArrayRow(
                $rowVariable,
                $arrayPath,
                $arrayPathExpression,
                $rowIndexVariable,
                arrayDepth: $context->arrayDepth + 1
            )
        );
    }

    /**
     * @param FormNode[] $children
     */
    private function renderChildrenRow(array $children, FormNodeRenderContext $context, int $gutter = 16): AbstractHtmlElement
    {
        $row = El::double('el-row')->setAttr(':gutter', $gutter);
        $this->appendRenderedChildren($row, $children, $context->withInline(false));

        return $row;
    }

    /**
     * @param FormNode[] $children
     */
    private function renderChildrenBodyContainer(
        string $className,
        array $children,
        FormNodeRenderContext $context,
        int $gutter = 16
    ): AbstractHtmlElement {
        return El::double('div')
            ->addClass($className)
            ->append($this->renderChildrenRow($children, $context, $gutter));
    }

    private function renderSectionHeader(
        SectionNode $section,
        ?RenderContext $renderContext = null
    ): ?AbstractHtmlElement
    {
        if ($section->title() === '' && $section->descriptionText() === null && $section->getHeaderActions() === []) {
            return null;
        }

        $header = El::double('div')->addClass('sc-v2-form-section__header');
        $heading = El::double('div')->addClass('sc-v2-form-section__heading');
        $headingBody = El::double('div')->addClass('sc-v2-form-section__heading-body');

        if ($section->title() !== '') {
            $headingBody->append(El::double('h3')->append($section->title()));
        }
        if ($section->descriptionText() !== null && $section->descriptionText() !== '') {
            $headingBody->append(El::double('p')->append($section->descriptionText()));
        }

        $heading->append($headingBody);

        if ($section->getHeaderActions() !== []) {
            $actions = El::double('div')->addClass('sc-v2-form-section__actions');
            foreach ($section->getHeaderActions() as $action) {
                $actions->append($this->actionButtonRenderer->render($action, false, 'small', null, $renderContext, 'page-header'));
            }
            $heading->append($actions);
        }

        return $header->append($heading);
    }

    private function renderBlockTitleHeader(?string $title, string $className): ?AbstractHtmlElement
    {
        $title = trim((string) $title);
        if ($title === '') {
            return null;
        }

        return El::double('div')
            ->addClass($className)
            ->append(El::double('h4')->append($title));
    }

    private function renderAddRowFooter(
        string $className,
        string $buttonText,
        string $clickExpression,
        ?string $disabledExpression = null
    ): AbstractHtmlElement {
        $buttonAttrs = [
                'type' => 'primary',
                'plain' => '',
                '@click' => $clickExpression,
            ];
        if ($disabledExpression !== null) {
            $buttonAttrs[':disabled'] = $disabledExpression;
        }

        return El::double('div')->addClass($className)->append(
            El::double('el-button')->setAttrs($buttonAttrs)->append($buttonText)
        );
    }

    /**
     * @param FormNode[] $children
     */
    private function appendRenderedChildren(
        AbstractHtmlElement $container,
        array $children,
        FormNodeRenderContext $context
    ): void {
        foreach ($children as $child) {
            $rendered = $this->renderNode($child, $context);
            if ($rendered->toHtml()) {
                $container->append($rendered);
            }
        }
    }

    private function contextForConditionalNode(FormNodeRenderContext $context, FormNode $node): FormNodeRenderContext
    {
        if (!$node instanceof ConditionalFormNode) {
            return $context;
        }

        $nodePathExpression = $this->nodePathExpression($node, $context);
        $nodeMeta = $this->buildNodeExpressionMeta($node);
        $disabledWhen = $node->isDisabled()
            ? 'true'
            : $this->buildNodeExpression($node->getDisabledWhen(), $context, $nodePathExpression, $nodeMeta);
        $readonlyWhen = $this->buildNodeExpression($node->getReadonlyWhen(), $context, $nodePathExpression, $nodeMeta);

        return $context
            ->mergeDisabledWhen($disabledWhen)
            ->mergeReadonlyWhen($readonlyWhen);
    }

    private function buildNodeExpression(
        ?\Stringable $expression,
        FormNodeRenderContext $context,
        ?string $nodePathExpression = null,
        array $nodeMeta = []
    ): ?string {
        $scopeExpression = $context->options->formScope === null || trim($context->options->formScope) === ''
            ? 'null'
            : $this->jsValue($context->options->formScope);

        return $this->buildFieldExpression(
            $expression,
            $context->modelName,
            $this->formModelExpression($context->modelName, $context->options->formScope),
            $scopeExpression,
            $nodePathExpression,
            $nodeMeta
        );
    }

    private function nodePathExpression(FormNode $node, FormNodeRenderContext $context): ?string
    {
        if ($node instanceof Field) {
            return $this->jsLiteral($context->fieldPath($node->name()));
        }

        if ($node instanceof FormObjectGroup || $node instanceof FormArrayGroup) {
            return $this->jsLiteral(FormPath::resolve($context->pathPrefix, $node->name()));
        }

        return null;
    }

    private function buildNodeExpressionMeta(FormNode $node): array
    {
        $meta = [
            'node' => $node::class,
            'visible' => $node instanceof ConditionalFormNode ? $node->isVisible() : true,
            'disabled' => $node instanceof ConditionalFormNode ? $node->isDisabled() : false,
        ];

        if (method_exists($node, 'isReadonly')) {
            $meta['readonly'] = $node->isReadonly();
        }

        return $meta;
    }

    private function formModelExpression(string $modelName, ?string $formScope = null): string
    {
        $modelName = trim($modelName);
        if ($modelName === '') {
            $modelName = '{}';
        }

        if ($formScope === null || trim($formScope) === '') {
            return $modelName;
        }

        return sprintf(
            '(typeof getFormModel === "function" ? (getFormModel(%s) || %s) : %s)',
            $this->jsValue($formScope),
            $modelName,
            $modelName
        );
    }

    private function resolveBooleanStateExpression(bool $staticEnabled, ?string ...$dynamicExpressions): ?string
    {
        if ($staticEnabled) {
            return 'true';
        }

        $conditions = array_values(array_filter(
            $dynamicExpressions,
            static fn(?string $expression): bool => is_string($expression) && trim($expression) !== ''
        ));

        if ($conditions === []) {
            return null;
        }

        if (in_array('true', $conditions, true)) {
            return 'true';
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return sprintf('(%s)', implode(') || (', $conditions));
    }

    private function interactionDisabledExpression(FormNodeRenderContext $context): ?string
    {
        return $this->resolveBooleanStateExpression(false, $context->disabledWhen, $context->readonlyWhen);
    }

    private function arrayFieldPropExpression(FormNodeRenderContext $context, string $fieldPath): string
    {
        if (!$context->isArrayRow()) {
            throw new RuntimeException('Array field prop expression requires array row context.');
        }

        return sprintf(
            'joinFormArrayFieldPath(%s, %s, %s)',
            $context->arrayPathExpression,
            $context->rowIndexExpression,
            $this->jsLiteral($fieldPath)
        );
    }

    private function resolveArrayPathExpression(FormNodeRenderContext $context, string $arrayPath): string
    {
        if (!$context->isArrayRow()) {
            return $this->jsLiteral($arrayPath);
        }

        return sprintf(
            'joinFormArrayFieldPath(%s, %s, %s)',
            $context->arrayPathExpression,
            $context->rowIndexExpression,
            $this->jsLiteral($arrayPath)
        );
    }

    /**
     * @return array{string, string}
     */
    private function arrayLoopVariables(FormNodeRenderContext $context): array
    {
        return [
            'row' . $context->arrayDepth,
            'rowIndex' . $context->arrayDepth,
        ];
    }

    private function tableLoopVariable(FormNodeRenderContext $context): string
    {
        return 'scope' . $context->arrayDepth;
    }

    private function tableRowModelExpression(string $tableScopeVariable): string
    {
        return $tableScopeVariable . '.row';
    }

    private function tableRowIndexExpression(string $tableScopeVariable): string
    {
        return $tableScopeVariable . '.$index';
    }

    private function renderFormTableScopedContent(string $tableScopeVariable): AbstractHtmlElement
    {
        if ($tableScopeVariable === 'scope') {
            return El::fictitious();
        }

        return El::double('template')->setAttr('v-for', sprintf('scope in [%s]', $tableScopeVariable));
    }

    private function renderCustomNode(CustomNode $customNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $rawContent = $customNode->content();
        if ($rawContent instanceof AbstractHtmlElement) {
            $content = $rawContent;
        } elseif ($rawContent instanceof Renderable) {
            if ($context->renderContext === null) {
                throw new RuntimeException('Renderable form custom node requires render context.');
            }
            if ($this->lightweightComponentRenderer === null || !$this->lightweightComponentRenderer->supportsTree($rawContent)) {
                throw new InvalidArgumentException(
                    'Unsupported V2 form custom renderable tree: '
                    . $rawContent::class
                    . '. Forms::custom() only accepts lightweight layouts/blocks/displays.'
                );
            }

            $content = $this->lightweightComponentRenderer->render(
                $rawContent,
                $context->renderContext,
                $this->customRenderableEventContextExpression($context)
            );
        } else {
            $content = El::double('div')->append($rawContent);
        }
        $content = $this->applyRenderableAttributes($content, $customNode->getRenderAttributes());

        if ($context->inline) {
            return $content;
        }

        return $this->wrapBlockNode($content, $customNode);
    }

    private function wrapBlockNode(AbstractHtmlElement $content, FormNode $node): AbstractHtmlElement
    {
        $root = $this->makeBlockNode($content, $node);

        return $this->appendAfterSpanColumn($root, $node->getAfterSpan());
    }

    private function makeBlockNode(AbstractHtmlElement $content, FormNode $node): AbstractHtmlElement
    {
        return El::double('el-col')->setAttr(':span', $node->getSpan())->append($content);
    }

    private function appendAfterSpanColumn(AbstractHtmlElement $root, int $afterSpan): AbstractHtmlElement
    {
        if ($afterSpan <= 0) {
            return $root;
        }

        $root->after(El::double('el-col')->setAttr(':span', $afterSpan));

        return $root->getParent() ?: $root;
    }

    private function customRenderableEventContextExpression(FormNodeRenderContext $context): ?string
    {
        $modelName = trim($context->modelName);
        if ($modelName === '') {
            return null;
        }

        return sprintf('{ model: %s }', $modelName);
    }

    private function renderFilterActions(Form $form, FormRenderOptions $options): AbstractHtmlElement
    {
        $submitButton = El::double('el-button')->setAttrs([
            'type' => 'primary',
            '@click' => $options->submitMethod ?? 'submitFilters',
        ])->append($form->getSubmitLabel());

        $resetButton = El::double('el-button')->setAttrs([
            '@click' => $options->resetMethod ?? 'resetFilters',
        ])->append($form->getResetLabel());

        $actionItem = El::double('el-form-item')->setAttr('label-width', 0)
            ->append(
                El::double('div')->addClass('sc-v2-filters__actions')->append(
                    $submitButton,
                    $resetButton
                )
            );

        if ($form->isInline()) {
            return $actionItem;
        }

        return El::double('el-row')->append(
            El::double('el-col')->setAttr(':span', 24)->append($actionItem)
        );
    }

    private function renderFooterActions(Form $form, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $footer = El::double('div')->addClass('sc-v2-form__footer');

        foreach ($form->getFooterActions() as $action) {
            $footer->append(
                $this->actionButtonRenderer->render(
                    $action,
                    false,
                    'default',
                    null,
                    $context->renderContext,
                    'default',
                    null,
                    $context->options->formScope
                )
            );
        }

        return $footer;
    }

    private function resolveNodeRendererMethod(FormNode $node): ?string
    {
        return $this->resolveClassMappedMethod($node, self::NODE_RENDERERS);
    }
}
