<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

final class ColumnRenderer
{
    use BuildsJsExpressions;

    public function render(Column $column): AbstractHtmlElement
    {
        $attrs = [
            'label' => $column->label(),
            'prop' => $column->prop(),
            ':show-overflow-tooltip' => 'true',
        ];

        if ($column->getWidth()) {
            $attrs['width'] = $column->getWidth();
        }
        if ($column->getMinWidth()) {
            $attrs['min-width'] = $column->getMinWidth();
        }
        if ($column->getAlign()) {
            $attrs['align'] = $column->getAlign();
        }
        if ($column->isSortable()) {
            $attrs['sortable'] = 'custom';
        }

        $element = El::double('el-table-column')->setAttrs($attrs);

        if ($column->getFormat()) {
            $element->append($this->renderFormatTemplate($column));

            return $element;
        }

        if ($column->getDisplay()) {
            $element->append($this->renderDisplayTemplate($column));

            return $element;
        }

        $element->append($this->renderPlainColumnTemplate($column));

        return $element;
    }

    private function renderFormatTemplate(Column $column): AbstractHtmlElement
    {
        $template = El::double('template')->setAttr('#default', 'scope');
        $format = trim($column->getFormat());
        $template->append(str_starts_with($format, '<') ? El::fromCode($format) : $format);

        return $template;
    }

    private function renderDisplayTemplate(Column $column): AbstractHtmlElement
    {
        $display = $column->getDisplay() ?? [];
        $template = El::double('template')->setAttr('#default', 'scope');

        return match ($display['type'] ?? '') {
            'mapping' => $this->renderMappingColumnTemplate($template, $column, $display),
            'tag' => $this->renderTagColumnTemplate($template, $column, $display),
            'image' => $this->renderImageColumnTemplate($template, $column, $display),
            'images' => $this->renderImagesColumnTemplate($template, $column, $display),
            'boolean' => $this->renderBooleanColumnTemplate($template, $column, $display),
            'boolean_tag' => $this->renderBooleanTagColumnTemplate($template, $column, $display),
            'datetime' => $this->renderDatetimeColumnTemplate($template, $column, $display),
            default => $this->renderPlainColumnTemplate($column),
        };
    }

    private function renderPlainColumnTemplate(Column $column): AbstractHtmlElement
    {
        $template = El::double('template')->setAttr('#default', 'scope');
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $displayExpression = $this->runtimeMethodCall('resolveColumnDisplayValue', $valueExpression);

        $template->append($this->aliasTemplate(
            'displayValue',
            $displayExpression,
            El::double('span')
                ->setAttr('v-if', '!isColumnDisplayBlank(displayValue)')
                ->append('{{ displayValue }}'),
            $this->placeholderSpan($column, 'v-else')
        ));

        return $template;
    }

    private function renderMappingColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $options = JsonExpressionEncoder::encodeCompact(array_values($display['options'] ?? []));
        $separator = $this->jsLiteral($display['separator'] ?? ', ');
        $labelExpression = $this->runtimeMethodCall('resolveColumnMappingLabel', $valueExpression, $options, $separator);

        $template->append($this->aliasTemplate(
            'displayLabel',
            $labelExpression,
            El::double('span')
                ->setAttr('v-if', '!isColumnDisplayBlank(displayLabel)')
                ->append('{{ displayLabel }}'),
            $this->placeholderSpan($column, 'v-else')
        ));

        return $template;
    }

    private function renderTagColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $options = JsonExpressionEncoder::encodeCompact(array_values($display['options'] ?? []));
        $metaExpression = $this->runtimeMethodCall(
            'resolveColumnTagMeta',
            $valueExpression,
            $options,
            $this->jsLiteral($display['defaultType'] ?? 'info')
        );
        $labelExpression = "(displayMeta?.label ?? '')";
        $typeExpression = "(displayMeta?.type ?? 'info')";

        $template->append($this->aliasTemplate(
            'displayMeta',
            $metaExpression,
            El::double('el-tag')
                ->setAttr('v-if', '!isColumnDisplayBlank(' . $labelExpression . ')')
                ->setAttr(':type', $typeExpression)
                ->append('{{ ' . $labelExpression . ' }}'),
            $this->placeholderSpan($column, 'v-else')
        ));

        return $template;
    }

    private function renderImageColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $style = sprintf(
            'width:%dpx;height:%dpx;border-radius:6px',
            (int)($display['width'] ?? 60),
            (int)($display['height'] ?? 60)
        );

        $template->append(
            El::double('el-image')->setAttrs([
                'v-if' => '!isColumnDisplayBlank(' . $valueExpression . ')',
                ':src' => $valueExpression,
                ':preview-src-list' => '[' . $valueExpression . ']',
                ':preview-teleported' => 'true',
                'fit' => (string)($display['fit'] ?? 'cover'),
                'style' => $style,
                'hide-on-click-modal' => '',
            ]),
            $this->placeholderSpan($column, 'v-else')
        );

        return $template;
    }

    private function renderImagesColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $srcExpression = $this->jsReadableAccessor('item', (string)($display['srcPath'] ?? 'url'));
        $previewExpression = ($display['srcPath'] ?? 'url') === ''
            ? $valueExpression
            : sprintf("%s.map((item) => %s).filter((value) => value !== '' && value !== null && value !== undefined)", $valueExpression, $srcExpression);
        $style = sprintf(
            'width:%dpx;height:%dpx;border-radius:6px',
            (int)($display['width'] ?? 60),
            (int)($display['height'] ?? 60)
        );

        $template->append(
            El::double('div')
                ->addClass('sc-v2-table__images')
                ->setAttr('v-if', 'Array.isArray(' . $valueExpression . ') && ' . $valueExpression . '.length > 0')
                ->append(
                    El::double('template')->setAttr(
                        'v-for',
                        sprintf('(item, imageIndex) in %s.slice(0, %d)', $valueExpression, (int)($display['previewNumber'] ?? 3))
                    )->append(
                        El::double('el-image')->setAttrs([
                            ':key' => 'imageIndex',
                            ':src' => $srcExpression,
                            ':preview-src-list' => $previewExpression,
                            ':initial-index' => 'imageIndex',
                            ':preview-teleported' => 'true',
                            'fit' => (string)($display['fit'] ?? 'cover'),
                            'style' => $style,
                            'hide-on-click-modal' => '',
                        ])
                    )
                ),
            $this->placeholderSpan($column, 'v-else')
        );

        return $template;
    }

    private function renderBooleanColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $truthyCheck = $this->runtimeMethodCall('isColumnTruthy', $valueExpression);
        $falsyCheck = $this->runtimeMethodCall('isColumnFalsy', $valueExpression);

        $template->append(
            El::double('span')
                ->setAttr('v-if', $truthyCheck)
                ->append((string)($display['truthyLabel'] ?? '是')),
            El::double('span')
                ->setAttr('v-else-if', $falsyCheck)
                ->append((string)($display['falsyLabel'] ?? '否')),
            $this->placeholderSpan($column, 'v-else')
        );

        return $template;
    }

    private function renderBooleanTagColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $truthyCheck = $this->runtimeMethodCall('isColumnTruthy', $valueExpression);
        $falsyCheck = $this->runtimeMethodCall('isColumnFalsy', $valueExpression);

        $template->append(
            El::double('el-tag')
                ->setAttr('v-if', $truthyCheck)
                ->setAttr('type', (string)($display['truthyType'] ?? 'success'))
                ->append((string)($display['truthyLabel'] ?? '是')),
            El::double('el-tag')
                ->setAttr('v-else-if', $falsyCheck)
                ->setAttr('type', (string)($display['falsyType'] ?? 'info'))
                ->append((string)($display['falsyLabel'] ?? '否')),
            $this->placeholderSpan($column, 'v-else')
        );

        return $template;
    }

    private function renderDatetimeColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $displayExpression = $this->runtimeMethodCall(
            'formatColumnDatetime',
            $valueExpression,
            $this->jsLiteral((string)($display['format'] ?? 'YYYY-MM-DD HH:mm:ss'))
        );

        $template->append($this->aliasTemplate(
            'displayValue',
            $displayExpression,
            El::double('span')
                ->setAttr('v-if', '!isColumnDisplayBlank(displayValue)')
                ->append('{{ displayValue }}'),
            $this->placeholderSpan($column, 'v-else')
        ));

        return $template;
    }

    private function placeholderSpan(Column $column, string $conditionAttr): AbstractHtmlElement
    {
        return El::double('span')
            ->setAttr($conditionAttr)
            ->append($column->getPlaceholder());
    }

    private function aliasTemplate(string $alias, string $expression, AbstractHtmlElement ...$children): AbstractHtmlElement
    {
        return El::double('template')
            ->setAttr('v-for', sprintf('%s in [%s]', $alias, $expression))
            ->append(...$children);
    }

    private function runtimeMethodCall(string $method, string ...$arguments): string
    {
        return sprintf('%s(%s)', $method, implode(', ', $arguments));
    }
}
