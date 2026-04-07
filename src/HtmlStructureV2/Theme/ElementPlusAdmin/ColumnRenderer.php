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

    public function render(
        Column $column,
        ?TableRenderBindings $bindings = null,
        bool $settingsEnabled = false
    ): AbstractHtmlElement
    {
        if ($column->isSelectionColumn()) {
            return $this->renderSelectionColumn($column);
        }

        if ($column->isIndexColumn()) {
            return $this->renderIndexColumn($column);
        }

        if ($column->isExpandColumn()) {
            return $this->renderExpandColumn($column, $bindings);
        }

        $attrs = [
            'label' => $column->label(),
            ':show-overflow-tooltip' => $column->getShowOverflowTooltip() ? 'true' : 'false',
        ];
        if ($column->prop() !== '') {
            $attrs['prop'] = $column->prop();
        }
        $attrs = array_merge($attrs, $column->getAttrs());

        if ($settingsEnabled && $bindings !== null && $column->supportsSettings()) {
            $attrs['v-if'] = $bindings->columnVisibleExpression($column->prop());
            $attrs[':width'] = $bindings->columnWidthExpression($column->prop(), $column->getWidth());
            $attrs[':align'] = $bindings->columnAlignExpression($column->prop(), $column->getAlign());
            $attrs[':fixed'] = $bindings->columnFixedExpression($column->prop(), $column->getFixed());
        } else {
            if ($column->getWidth() !== null) {
                $attrs['width'] = $column->getWidth();
            }
            if ($column->getAlign()) {
                $attrs['align'] = $column->getAlign();
            }
            if ($column->getFixed()) {
                $attrs['fixed'] = $column->getFixed();
            }
        }

        if ($column->getMinWidth() !== null) {
            $attrs['min-width'] = $column->getMinWidth();
        }
        if ($column->isSortable()) {
            $attrs['sortable'] = 'custom';
        }

        $element = El::double('el-table-column')->setAttrs($attrs);

        if ($column->getFormat()) {
            $element->append($this->renderFormatTemplate($column));

            return $this->decorateColumnElement($column, $element);
        }

        if ($column->getDisplay()) {
            $element->append($this->renderDisplayTemplate($column, $bindings));

            return $this->decorateColumnElement($column, $element);
        }

        $element->append($this->renderPlainColumnTemplate($column));

        return $this->decorateColumnElement($column, $element);
    }

    private function renderFormatTemplate(Column $column): AbstractHtmlElement
    {
        $template = El::double('template')->setAttr('#default', 'scope');
        $format = trim($column->getFormat());
        $template->append(str_starts_with($format, '<') ? El::fromCode($format) : $format);

        return $template;
    }

    private function renderDisplayTemplate(Column $column, ?TableRenderBindings $bindings = null): AbstractHtmlElement
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
            'switch' => $this->renderSwitchColumnTemplate($template, $column, $display, $bindings),
            'datetime' => $this->renderDatetimeColumnTemplate($template, $column, $display),
            'open_page' => $this->renderOpenPageColumnTemplate($template, $column, $display, $bindings),
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
        $effectExpression = "(displayMeta?.effect ?? displayMeta?.theme ?? null)";

        $template->append($this->aliasTemplate(
            'displayMeta',
            $metaExpression,
            El::double('el-tag')
                ->setAttr('v-if', '!isColumnDisplayBlank(' . $labelExpression . ')')
                ->setAttr(':type', $typeExpression)
                ->setAttr(':effect', $effectExpression)
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

    private function renderSwitchColumnTemplate(
        AbstractHtmlElement $template,
        Column $column,
        array $display,
        ?TableRenderBindings $bindings = null
    ): AbstractHtmlElement {
        if ($bindings === null) {
            return $this->renderMappingColumnTemplate($template, $column, [
                'options' => $display['options'] ?? [],
                'separator' => ', ',
            ]);
        }

        $modelExpression = $this->jsModelAccessor('scope.row', $column->prop());
        $switchConfigExpression = JsonExpressionEncoder::encodeCompact([
            'requestUrl' => $display['requestUrl'] ?? '',
            'activeValue' => $display['activeValue'] ?? 1,
            'inactiveValue' => $display['inactiveValue'] ?? 0,
        ]);

        $template->append(
            El::double('el-switch')->setAttrs([
                'v-model' => $modelExpression,
                'inline-prompt' => '',
                'active-text' => (string)($display['activeText'] ?? '开'),
                'inactive-text' => (string)($display['inactiveText'] ?? '关'),
                ':active-value' => $this->jsLiteral($display['activeValue'] ?? 1),
                ':inactive-value' => $this->jsLiteral($display['inactiveValue'] ?? 0),
                '@change' => $bindings->switchChangeExpression($column->prop(), $switchConfigExpression),
            ])
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

    private function renderOpenPageColumnTemplate(
        AbstractHtmlElement $template,
        Column $column,
        array $display,
        ?TableRenderBindings $bindings = null
    ): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $displayExpression = $this->runtimeMethodCall('resolveColumnDisplayValue', $valueExpression);
        $clickExpression = $this->buildOpenPageClickExpression($column, $display, $bindings);
        $customElement = $this->normalizeOpenPageElement($display['element'] ?? null, $clickExpression);

        if ($customElement !== null) {
            $template->append($customElement);

            return $template;
        }

        $template->append($this->aliasTemplate(
            'openPageLabel',
            $displayExpression,
            El::double('el-link')
                ->setAttrs([
                    'v-if' => '!isColumnDisplayBlank(openPageLabel)',
                    'type' => 'primary',
                    '@click' => $clickExpression,
                ])
                ->append('{{ openPageLabel }}'),
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

    private function renderSelectionColumn(Column $column): AbstractHtmlElement
    {
        $attrs = array_merge([
            'type' => 'selection',
            'width' => $column->getWidth() ?? '48',
            'align' => $column->getAlign() ?? 'center',
        ], $column->getAttrs());

        if ($column->getFixed()) {
            $attrs['fixed'] = $column->getFixed();
        }

        return El::double('el-table-column')->setAttrs($attrs);
    }

    private function renderIndexColumn(Column $column): AbstractHtmlElement
    {
        $attrs = array_merge([
            'type' => 'index',
            'label' => $column->label(),
            'width' => $column->getWidth() ?? '80',
            'align' => $column->getAlign() ?? 'center',
        ], $column->getAttrs());

        if ($column->getFixed()) {
            $attrs['fixed'] = $column->getFixed();
        }

        return El::double('el-table-column')->setAttrs($attrs);
    }

    private function renderExpandColumn(Column $column, ?TableRenderBindings $bindings = null): AbstractHtmlElement
    {
        $attrs = array_merge([
            'type' => 'expand',
            'label' => $column->label(),
        ], $column->getAttrs());

        $element = El::double('el-table-column')->setAttrs($attrs);

        if ($column->getFormat()) {
            $element->append($this->renderFormatTemplate($column));

            return $this->decorateColumnElement($column, $element);
        }

        if ($column->getDisplay()) {
            $element->append($this->renderDisplayTemplate($column, $bindings));

            return $this->decorateColumnElement($column, $element);
        }

        if ($column->prop() !== '') {
            $element->append($this->renderPlainColumnTemplate($column));
        }

        return $this->decorateColumnElement($column, $element);
    }

    private function decorateColumnElement(Column $column, AbstractHtmlElement $element): AbstractHtmlElement
    {
        $template = $this->resolveContentTemplate($element);
        if ($template === null) {
            return $element;
        }

        foreach ($column->getAppendContent() as $content) {
            $template->append($this->normalizeTemplateNode($content));
        }

        if ($column->getTip() !== []) {
            $template->append($this->renderTipPopover($column->getTip()));
        }

        return $element;
    }

    private function resolveContentTemplate(AbstractHtmlElement $element): ?AbstractHtmlElement
    {
        if (!method_exists($element, 'getChildren')) {
            return null;
        }

        foreach ($element->getChildren() as $child) {
            if (method_exists($child, 'hasAttr') && $child->hasAttr('#default')) {
                return $child;
            }
        }

        return null;
    }

    private function normalizeTemplateNode(mixed $content): AbstractHtmlElement|string
    {
        if ($content instanceof AbstractHtmlElement) {
            return $content->copy();
        }

        $string = (string)$content;

        return str_starts_with(trim($string), '<')
            ? El::fromCode($string)
            : $string;
    }

    private function renderTipPopover(array $tip): AbstractHtmlElement
    {
        $icon = $this->normalizeTipIcon($tip['icon'] ?? 'WarningFilled');
        $popover = El::double('el-popover')->setAttrs(array_merge([
            'trigger' => 'click',
        ], is_array($tip['attrs'] ?? null) ? $tip['attrs'] : []));

        $popover->append(
            El::double('template')
                ->setAttr('#reference', '')
                ->append($icon),
            $this->normalizeTemplateNode($tip['tip'] ?? '无')
        );

        return $popover;
    }

    private function normalizeTipIcon(mixed $icon): AbstractHtmlElement
    {
        if ($icon instanceof AbstractHtmlElement) {
            return $icon->copy();
        }

        $string = trim((string)$icon);
        if ($string !== '' && str_starts_with($string, '<')) {
            return El::fromCode($string);
        }

        $component = $string !== '' ? preg_replace('/([a-z])([A-Z])/', '$1-$2', $string) : 'warning-filled';

        return El::double('el-icon')
            ->append(El::double(strtolower((string)$component)))
            ->setAttr('style', 'cursor:pointer;margin-left:6px;color:var(--el-color-warning);vertical-align:middle');
    }

    private function normalizeOpenPageElement(mixed $element, string $clickExpression): ?AbstractHtmlElement
    {
        if ($element === null || $element === '') {
            return null;
        }

        if ($element instanceof AbstractHtmlElement) {
            $normalized = $element->copy();
        } else {
            $string = trim((string)$element);
            $normalized = str_starts_with($string, '<')
                ? El::fromCode($string)
                : El::double('el-link')->setAttr('type', 'primary')->append($string);
        }

        if (method_exists($normalized, 'setAttrIfNotExist')) {
            $normalized->setAttrIfNotExist('@click', $clickExpression);
        }

        return $normalized;
    }

    private function buildOpenPageClickExpression(
        Column $column,
        array $display,
        ?TableRenderBindings $bindings = null
    ): string
    {
        if (($display['openType'] ?? 'dialog') === 'dialog' && $bindings !== null) {
            $dialogKey = $column->managedOpenPageDialogKey($bindings->tableKey());
            if ($dialogKey !== null) {
                return $bindings->openDialogExpression($dialogKey, 'scope.row');
            }
        }

        $url = $this->jsLiteral((string)($display['url'] ?? ''));
        $params = JsonExpressionEncoder::encodeCompact($display['params'] ?? []);
        $features = $this->jsLiteral($this->buildOpenPageFeatures($display['config'] ?? []));
        $openType = $this->jsLiteral((string)($display['openType'] ?? 'dialog'));
        $titleTemplate = $this->jsLiteral($this->buildOpenPageTitleTemplate($column, $display));

        return $this->runtimeMethodCall(
            'openColumnPage',
            'scope',
            $url,
            $params,
            $features,
            $openType,
            $titleTemplate,
            '$event'
        );
    }

    private function buildOpenPageTitleTemplate(Column $column, array $display): string
    {
        $config = is_array($display['config'] ?? null) ? $display['config'] : [];
        $title = trim((string)($config['title'] ?? ''));

        if ($title === '') {
            if ($column->prop() !== '') {
                return sprintf('查看【{%s}】详情', $column->prop());
            }

            return $column->label() !== ''
                ? sprintf('查看【%s】详情', $column->label())
                : '查看详情';
        }

        $normalized = preg_replace_callback(
            '/{{\s*@?([A-Za-z0-9_.]+)\s*}}/',
            static fn(array $matches): string => '{' . ($matches[1] ?? '') . '}',
            $title
        );

        return is_string($normalized) && $normalized !== ''
            ? $normalized
            : $title;
    }

    private function buildOpenPageFeatures(array $config): string
    {
        $width = $this->normalizePopupDimension($config['width'] ?? 1000, 1000);
        $height = $this->normalizePopupDimension($config['height'] ?? 760, 760);

        return sprintf(
            'popup=yes,noopener=yes,noreferrer=yes,width=%d,height=%d',
            $width,
            $height
        );
    }

    private function normalizePopupDimension(mixed $value, int $fallback): int
    {
        if (is_int($value) || is_float($value)) {
            return max(200, (int)$value);
        }

        if (!is_string($value)) {
            return $fallback;
        }

        if (preg_match('/(\d+)/', $value, $matches) === 1) {
            return max(200, (int)$matches[1]);
        }

        return $fallback;
    }
}
