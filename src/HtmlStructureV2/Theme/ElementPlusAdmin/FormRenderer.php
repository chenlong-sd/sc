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
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\FormPath;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnSchema;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnWalker;
use Sc\Util\HtmlStructureV2\Support\ResolvesClassMappedMethod;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;
use RuntimeException;

final class FormRenderer
{
    use BuildsJsExpressions;
    use ResolvesClassMappedMethod;

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
        $attrs = [
            ':model' => $modelName,
            'label-width' => $form->getLabelWidth(),
        ];

        if ($options->ref !== null) {
            $attrs['ref'] = $options->ref;
        }
        if ($options->rules !== null) {
            $attrs[':rules'] = $options->rules;
        }

        $element = El::double('el-form')->setAttrs($attrs);
        $context = new FormNodeRenderContext(
            modelName: $modelName,
            inline: $form->isInline(),
            options: $options,
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

        if ($options->isFilterMode()) {
            $element->append($this->renderFilterActions($form, $options));
        }

        return $element;
    }

    private function renderNode(FormNode $node, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $method = $this->resolveNodeRendererMethod($node);
        if ($method === null) {
            throw new InvalidArgumentException('Unsupported V2 form node: ' . $node::class);
        }

        return $this->{$method}($node, $context);
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
                $context->renderContext
            );
        }

        return $this->fieldRenderer->renderScoped(
            $field,
            $context->modelName,
            $this->arrayFieldPropExpression($context, $fieldPath),
            $fieldPath,
            $context->inline,
            $context->options,
            $context->renderContext
        );
    }

    private function renderSectionNode(SectionNode $section, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-form-section');
        $header = $this->renderSectionHeader($section, $context->renderContext);
        if ($header !== null) {
            $body->append($header);
        }
        $body->append($this->renderChildrenRow($section->getChildren(), $context));

        $wrapped = $section->isPlain()
            ? $body
            : El::double('el-card')->append($body);

        return $this->wrapBlockNode($wrapped, $section->getSpan());
    }

    private function renderInlineNode(InlineNode $inlineNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-form-inline');
        $this->appendRenderedChildren($body, $inlineNode->getChildren(), $context->withInline(true));

        return $this->wrapBlockNode($body, $inlineNode->getSpan());
    }

    private function renderGridNode(GridNode $gridNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $row = El::double('el-row')->setAttr(':gutter', $gridNode->getGutter());
        $this->appendRenderedChildren($row, $gridNode->getChildren(), $context->withInline(false));

        return $this->wrapBlockNode($row, $gridNode->getSpan());
    }

    private function renderTabsNode(TabsNode $tabsNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $tabs = El::double('el-tabs')->addClass('sc-v2-form-tabs');

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
            $pane = El::double('el-tab-pane')->setAttrs([
                'label' => $tab->label(),
                'name' => (string) $index,
            ]);
            if ($tab->isLazy()) {
                $pane->setAttr('lazy', '');
            }

            $pane->append($this->renderChildrenBodyContainer('sc-v2-form-tabs__pane', $tab->getChildren(), $context));
            $tabs->append($pane);
        }

        return $this->wrapBlockNode($tabs, $tabsNode->getSpan());
    }

    private function renderCollapseNode(CollapseNode $collapseNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $collapse = El::double('el-collapse')->addClass('sc-v2-form-collapse');
        if ($collapseNode->isAccordion()) {
            $collapse->setAttr('accordion', '');
        }

        foreach ($collapseNode->getItems() as $index => $item) {
            $collapse->append(
                El::double('el-collapse-item')->setAttrs([
                    'title' => $item->title(),
                    'name' => (string) $index,
                ])->append($this->renderChildrenBodyContainer(
                    'sc-v2-form-collapse__item-body',
                    $item->getChildren(),
                    $context
                ))
            );
        }

        return $this->wrapBlockNode($collapse, $collapseNode->getSpan());
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
        $content = El::fictitious();
        $nextPrefix = FormPath::resolve($context->pathPrefix, $group->name());
        $nextModelName = $this->jsModelAccessor($context->modelName, $group->name());
        $this->appendRenderedChildren(
            $content,
            $group->getChildren(),
            $context->nested($nextModelName, $nextPrefix)
        );

        return $content;
    }

    private function renderArrayGroup(FormArrayGroup $group, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $arrayPath = FormPath::resolve($context->pathPrefix, $group->name());
        $scopeLiteral = $this->jsLiteral($context->options->remoteScope ?? $context->options->uploadScope ?? 'form');
        $arrayPathExpression = $this->resolveArrayPathExpression($context, $arrayPath);
        $tableScopeVariable = $this->tableLoopVariable($context);
        [$rowVariable, $rowIndexVariable] = $this->arrayLoopVariables($context);

        $body = El::double('div')->addClass('sc-v2-form-array');
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
            $rowIndexVariable
        ));
        $card->append($this->renderArrayGroupRowBody(
            $group,
            $context,
            $arrayPath,
            $arrayPathExpression,
            $rowVariable,
            $rowIndexVariable
        ));
        $template->append($card);
        $rows->append($template);
        $body->append($rows);

        if ($group->isAddable()) {
            $body->append($this->renderAddRowFooter(
                'sc-v2-form-array__footer',
                $group->getAddButtonText(),
                sprintf('addFormArrayRow(%s, %s)', $scopeLiteral, $arrayPathExpression)
            ));
        }

        if ($context->inline) {
            return $body;
        }

        return $this->wrapBlockNode($body, $group->getSpan());
    }

    private function renderFormTable(FormTable $table, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $columns = $this->formTableColumnWalker->build($table->getChildren(), $table->name());

        $arrayPath = FormPath::resolve($context->pathPrefix, $table->name());
        $scopeLiteral = $this->jsLiteral($context->options->remoteScope ?? $context->options->uploadScope ?? 'form');
        $arrayPathExpression = $this->resolveArrayPathExpression($context, $arrayPath);
        $tableScopeVariable = $this->tableLoopVariable($context);

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
        ]);

        foreach ($columns as $columnSchema) {
            $tableElement->append(
                $this->renderFormTableColumnNode(
                    $columnSchema,
                    $arrayPathExpression,
                    $context->options,
                    $context->renderContext,
                    $context->arrayDepth,
                    $tableScopeVariable
                )
            );
        }

        if ($table->isReorderable() || $table->isRemovable()) {
            $tableElement->append($this->renderFormTableActionColumn(
                $table,
                $scopeLiteral,
                $arrayPathExpression,
                $tableScopeVariable,
                $this->tableRowIndexExpression($tableScopeVariable)
            ));
        }

        $body->append($tableElement);

        if ($table->isAddable()) {
            $body->append($this->renderAddRowFooter(
                'sc-v2-form-table__footer',
                $table->getAddButtonText(),
                sprintf('addFormArrayRow(%s, %s)', $scopeLiteral, $arrayPathExpression)
            ));
        }

        if ($context->inline) {
            return $body;
        }

        return $this->wrapBlockNode($body, $table->getSpan());
    }

    private function renderFormTableColumnNode(
        FormTableColumnSchema $columnSchema,
        string $arrayPathExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        int $tableArrayDepth = 0,
        string $tableScopeVariable = 'scope'
    ): AbstractHtmlElement {
        $column = El::double('el-table-column');
        if ($columnSchema->label() !== '') {
            $column->setAttr('label', $columnSchema->label());
        }

        if ($columnSchema->isGroup()) {
            foreach ($columnSchema->children() as $childColumn) {
                $column->append($this->renderFormTableColumnNode(
                    $childColumn,
                    $arrayPathExpression,
                    $options,
                    $renderContext,
                    $tableArrayDepth,
                    $tableScopeVariable
                ));
            }

            return $column;
        }

        $tableRowModelExpression = $this->tableRowModelExpression($tableScopeVariable);
        $tableRowIndexExpression = $this->tableRowIndexExpression($tableScopeVariable);
        $template = El::double('template')->setAttr('#default', $tableScopeVariable);

        if ($columnSchema->isField()) {
            $field = $columnSchema->field();
            if ($field === null || $field->type() === FieldType::HIDDEN) {
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
            $template->append(
                $this->fieldRenderer->renderTableCell(
                    $field,
                    $fieldModelName,
                    $columnSchema->path(),
                    $propExpression,
                    $options,
                    $renderContext
                )
            );
        } elseif ($columnSchema->arrayGroup() !== null) {
            $template->append($this->renderFormTableArrayColumn(
                $columnSchema,
                $arrayPathExpression,
                $options,
                $renderContext,
                $tableArrayDepth,
                $tableScopeVariable
            ));
        } else {
            $customNode = $columnSchema->customNode();
            if ($customNode === null) {
                return El::fictitious();
            }

            $template->append($this->renderCustomNode(
                $customNode,
                new FormNodeRenderContext(
                    modelName: $tableRowModelExpression,
                    inline: true,
                    options: $options,
                    renderContext: $renderContext
                )
            ));
        }

        $column->append($template);

        return $column;
    }

    private function renderFormTableArrayColumn(
        FormTableColumnSchema $columnSchema,
        string $arrayPathExpression,
        FormRenderOptions $options,
        ?RenderContext $renderContext = null,
        int $tableArrayDepth = 0,
        string $tableScopeVariable = 'scope'
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
            options: $options,
            renderContext: $renderContext,
            arrayPath: '__table_row__',
            arrayPathExpression: $arrayPathExpression,
            rowIndexExpression: $tableRowIndexExpression,
            arrayDepth: $tableArrayDepth + 1,
        );

        if ($group instanceof FormTable) {
            return $this->renderFormTable($group, $rowContext);
        }

        return $this->renderArrayGroup($group, $rowContext);
    }

    private function renderFormTableActionColumn(
        FormTable $table,
        string $scopeLiteral,
        string $arrayPathExpression,
        string $tableScopeVariable,
        string $rowIndexExpression
    ): AbstractHtmlElement {
        $column = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'width' => $table->isReorderable() ? '180' : '120',
            'fixed' => 'right',
        ]);

        $template = El::double('template')->setAttr('#default', $tableScopeVariable);
        $actions = El::double('div')->addClass('sc-v2-form-table__actions');

        if ($table->isReorderable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'size' => 'small',
                    ':disabled' => sprintf('%s === 0', $rowIndexExpression),
                    '@click' => sprintf(
                        'moveFormArrayRow(%s, %s, %s, "up")',
                        $scopeLiteral,
                        $arrayPathExpression,
                        $rowIndexExpression
                    ),
                ])->append('上移'),
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'size' => 'small',
                    ':disabled' => sprintf(
                        '%s >= getFormArrayRows(%s, %s).length - 1',
                        $rowIndexExpression,
                        $scopeLiteral,
                        $arrayPathExpression
                    ),
                    '@click' => sprintf(
                        'moveFormArrayRow(%s, %s, %s, "down")',
                        $scopeLiteral,
                        $arrayPathExpression,
                        $rowIndexExpression
                    ),
                ])->append('下移')
            );
        }

        if ($table->isRemovable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'danger',
                    'size' => 'small',
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
        string $rowIndexVariable
    ): AbstractHtmlElement {
        $header = El::double('template')->setAttr('#header');
        $container = El::double('div')->addClass('sc-v2-form-array__item-header');
        $container->append(
            El::double('span')->addClass('sc-v2-form-array__item-title')->append(
                sprintf('第 {{ %s + 1 }} 组', $rowIndexVariable)
            )
        );

        if ($group->isReorderable() || $group->isRemovable()) {
            $actions = El::double('div')->addClass('sc-v2-form-array__item-actions');

            if ($group->isReorderable()) {
                $actions->append(
                    El::double('el-button')->setAttrs([
                        'link' => '',
                        'type' => 'primary',
                        'size' => 'small',
                        ':disabled' => sprintf('%s === 0', $rowIndexVariable),
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
                            '%s >= getFormArrayRows(%s, %s).length - 1',
                            $rowIndexVariable,
                            $scopeLiteral,
                            $arrayPathExpression
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
                $actions->append($this->actionButtonRenderer->render($action, false, 'small', null, $renderContext));
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
        string $clickExpression
    ): AbstractHtmlElement {
        return El::double('div')->addClass($className)->append(
            El::double('el-button')->setAttrs([
                'type' => 'primary',
                'plain' => '',
                '@click' => $clickExpression,
            ])->append($buttonText)
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

        if ($context->inline) {
            return $content;
        }

        return $this->wrapBlockNode($content, $customNode->getSpan());
    }

    private function wrapBlockNode(AbstractHtmlElement $content, int $span): AbstractHtmlElement
    {
        return El::double('el-col')->setAttr(':span', $span)->append($content);
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

    private function resolveNodeRendererMethod(FormNode $node): ?string
    {
        return $this->resolveClassMappedMethod($node, self::NODE_RENDERERS);
    }
}
