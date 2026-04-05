<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormObjectGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormTable;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\GridNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\InlineNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\SectionNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\FormPath;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnSchema;
use Sc\Util\HtmlStructureV2\Support\FormTableColumnWalker;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;
use RuntimeException;

final class FormRenderer
{
    use BuildsJsExpressions;

    private const NODE_RENDERERS = [
        Field::class => 'renderFieldNode',
        SectionNode::class => 'renderSectionNode',
        InlineNode::class => 'renderInlineNode',
        GridNode::class => 'renderGridNode',
        FormObjectGroup::class => 'renderObjectGroup',
        FormTable::class => 'renderFormTable',
        FormArrayGroup::class => 'renderArrayGroup',
        CustomNode::class => 'renderCustomNode',
    ];

    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
        private readonly FormTableColumnWalker $formTableColumnWalker = new FormTableColumnWalker(),
    ) {
    }

    public function render(Form $form, string $modelName, FormRenderOptions $options): AbstractHtmlElement
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
                $context->options
            );
        }

        return $this->fieldRenderer->renderScoped(
            $field,
            $context->modelName,
            $this->arrayFieldPropExpression($context, $fieldPath),
            $fieldPath,
            $context->inline,
            $context->options
        );
    }

    private function renderSectionNode(SectionNode $section, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-form-section');

        if ($section->title() !== '' || $section->descriptionText() !== null) {
            $header = El::double('div')->addClass('sc-v2-form-section__header');
            if ($section->title() !== '') {
                $header->append(El::double('h3')->append($section->title()));
            }
            if ($section->descriptionText() !== null && $section->descriptionText() !== '') {
                $header->append(El::double('p')->append($section->descriptionText()));
            }
            $body->append($header);
        }

        $grid = El::double('el-row')->setAttr(':gutter', 16);
        $this->appendRenderedChildren($grid, $section->getChildren(), $context->withInline(false));
        $body->append($grid);

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
        [$rowVariable, $rowIndexVariable] = $this->arrayLoopVariables($context);

        $body = El::double('div')->addClass('sc-v2-form-array');
        if ($group->getTitle()) {
            $body->append(
                El::double('div')->addClass('sc-v2-form-array__header')->append(
                    El::double('h4')->append($group->getTitle())
                )
            );
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
            $body->append(
                El::double('div')->addClass('sc-v2-form-array__footer')->append(
                    El::double('el-button')->setAttrs([
                        'type' => 'primary',
                        'plain' => '',
                        '@click' => sprintf(
                            'addFormArrayRow(%s, %s)',
                            $scopeLiteral,
                            $arrayPathExpression
                        ),
                    ])->append($group->getAddButtonText())
                )
            );
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

        $body = El::double('div')->addClass('sc-v2-form-table');
        if ($table->getTitle()) {
            $body->append(
                El::double('div')->addClass('sc-v2-form-table__header')->append(
                    El::double('h4')->append($table->getTitle())
                )
            );
        }

        $tableElement = El::double('el-table')->setAttrs([
            ':data' => sprintf('getFormArrayRows(%s, %s)', $scopeLiteral, $arrayPathExpression),
            ':border' => $table->useBorder() ? 'true' : 'false',
            'row-key' => '__sc_v2_row_key',
            'empty-text' => $table->getEmptyText(),
            'class' => 'sc-v2-form-table__table',
        ]);

        foreach ($columns as $columnSchema) {
            $tableElement->append($this->renderFormTableColumn($columnSchema, $arrayPathExpression, $context->options));
        }

        if ($table->isReorderable() || $table->isRemovable()) {
            $tableElement->append($this->renderFormTableActionColumn($table, $scopeLiteral, $arrayPathExpression));
        }

        $body->append($tableElement);

        if ($table->isAddable()) {
            $body->append(
                El::double('div')->addClass('sc-v2-form-table__footer')->append(
                    El::double('el-button')->setAttrs([
                        'type' => 'primary',
                        'plain' => '',
                        '@click' => sprintf(
                            'addFormArrayRow(%s, %s)',
                            $scopeLiteral,
                            $arrayPathExpression
                        ),
                    ])->append($table->getAddButtonText())
                )
            );
        }

        if ($context->inline) {
            return $body;
        }

        return $this->wrapBlockNode($body, $table->getSpan());
    }

    private function renderFormTableColumn(
        FormTableColumnSchema $columnSchema,
        string $arrayPathExpression,
        FormRenderOptions $options
    ): AbstractHtmlElement {
        $column = El::double('el-table-column');
        if ($columnSchema->label() !== '') {
            $column->setAttr('label', $columnSchema->label());
        }

        $template = El::double('template')->setAttr('#default', 'scope');

        if ($columnSchema->isField()) {
            $field = $columnSchema->field();
            if ($field === null || $field->type() === FieldType::HIDDEN) {
                return El::fictitious();
            }

            $fieldModelName = $columnSchema->modelPath() === ''
                ? 'scope.row'
                : $this->jsModelAccessor('scope.row', $columnSchema->modelPath());

            $propExpression = sprintf(
                'joinFormArrayFieldPath(%s, scope.$index, %s)',
                $arrayPathExpression,
                $this->jsLiteral($columnSchema->path())
            );
            $template->append(
                $this->fieldRenderer->renderTableCell(
                    $field,
                    $fieldModelName,
                    $columnSchema->path(),
                    $propExpression,
                    $options
                )
            );
        } else {
            $customNode = $columnSchema->customNode();
            if ($customNode === null) {
                return El::fictitious();
            }

            $template->append($this->renderCustomNode(
                $customNode,
                new FormNodeRenderContext(
                    modelName: 'scope.row',
                    inline: true,
                    options: $options
                )
            ));
        }

        $column->append($template);

        return $column;
    }

    private function renderFormTableActionColumn(
        FormTable $table,
        string $scopeLiteral,
        string $arrayPathExpression
    ): AbstractHtmlElement {
        $column = El::double('el-table-column')->setAttrs([
            'label' => '操作',
            'width' => $table->isReorderable() ? '180' : '120',
            'fixed' => 'right',
        ]);

        $template = El::double('template')->setAttr('#default', 'scope');
        $actions = El::double('div')->addClass('sc-v2-form-table__actions');

        if ($table->isReorderable()) {
            $actions->append(
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'size' => 'small',
                    ':disabled' => 'scope.$index === 0',
                    '@click' => sprintf(
                        'moveFormArrayRow(%s, %s, scope.$index, "up")',
                        $scopeLiteral,
                        $arrayPathExpression
                    ),
                ])->append('上移'),
                El::double('el-button')->setAttrs([
                    'link' => '',
                    'type' => 'primary',
                    'size' => 'small',
                    ':disabled' => sprintf(
                        'scope.$index >= getFormArrayRows(%s, %s).length - 1',
                        $scopeLiteral,
                        $arrayPathExpression
                    ),
                    '@click' => sprintf(
                        'moveFormArrayRow(%s, %s, scope.$index, "down")',
                        $scopeLiteral,
                        $arrayPathExpression
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
                        'removeFormArrayRow(%s, %s, scope.$index)',
                        $scopeLiteral,
                        $arrayPathExpression
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
        $body = El::double('div')->addClass('sc-v2-form-array__item-body');
        $row = El::double('el-row')->setAttr(':gutter', 16);
        $this->appendRenderedChildren(
            $row,
            $group->getChildren(),
            $context->forArrayRow(
                $rowVariable,
                $arrayPath,
                $arrayPathExpression,
                $rowIndexVariable,
                arrayDepth: $context->arrayDepth + 1
            )
        );
        $body->append($row);

        return $body;
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

    private function renderCustomNode(CustomNode $customNode, FormNodeRenderContext $context): AbstractHtmlElement
    {
        $content = $customNode->content() instanceof AbstractHtmlElement
            ? $customNode->content()
            : El::double('div')->append($customNode->content());

        if ($context->inline) {
            return $content;
        }

        return $this->wrapBlockNode($content, $customNode->getSpan());
    }

    private function wrapBlockNode(AbstractHtmlElement $content, int $span): AbstractHtmlElement
    {
        return El::double('el-col')->setAttr(':span', $span)->append($content);
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
        foreach (self::NODE_RENDERERS as $class => $method) {
            if ($node instanceof $class) {
                return $method;
            }
        }

        return null;
    }
}
