<?php

namespace Sc\Util\HtmlStructureV2\Theme;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\ThemeInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Page\AdminPage;
use Sc\Util\HtmlStructureV2\Page\CrudPage;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;
use Sc\Util\HtmlStructureV2\Support\StaticResource;

final class ElementPlusAdminTheme implements ThemeInterface
{
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
    .sc-v2-search__actions{display:flex;gap:12px;flex-wrap:wrap}
    .sc-v2-table__images{display:flex;gap:8px;flex-wrap:wrap}
    @media (max-width: 768px){
      #app{padding:16px}
      .sc-v2-page__header{flex-direction:column;align-items:stretch}
      .sc-v2-actions,.sc-v2-toolbar,.sc-v2-toolbar__actions,.sc-v2-search__actions{width:100%}
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
            $component instanceof CrudPage => $this->renderCrudPage($component, $context),
            $component instanceof AdminPage => $this->renderAdminPage($component, $context),
            $component instanceof Form => $this->renderStandaloneForm($component, $context),
            $component instanceof Table => $this->renderStandaloneTable($component, $context),
            $component instanceof Dialog => $this->renderDialog($component, $context, 'dialogForm', 'dialogVisible'),
            $component instanceof Action => $this->renderActionButton($component),
            default => throw new \InvalidArgumentException('Unsupported V2 renderable: ' . $component::class),
        };
    }

    private function renderCrudPage(CrudPage $page, RenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-page');
        $body->append($this->renderPageHeader($page));

        if ($page->getSearchForm()) {
            $searchCard = $this->card('筛选条件');
            $searchCard->append(
                $this->renderForm($page->getSearchForm(), 'searchModel', [
                    'mode' => 'search',
                    'ref' => 'searchFormRef',
                    'rules' => 'searchRules',
                    'submitMethod' => 'submitSearch',
                    'resetMethod' => 'resetSearch',
                'remoteOptionsState' => 'searchOptions',
                'remoteLoadingState' => 'searchOptionLoading',
                'remoteLoadMethod' => 'loadFormFieldOptions',
                'remoteScope' => 'search',
                'uploadFilesState' => 'searchUploadFiles',
                'uploadScope' => 'search',
                'uploadSuccessMethod' => 'handleUploadSuccess',
                'uploadRemoveMethod' => 'handleUploadRemove',
                'uploadExceedMethod' => 'handleUploadExceed',
                'uploadPreviewMethod' => 'handleUploadPreview',
                'linkageMethod' => 'applyFormLinkage',
            ])
            );
            $body->append($searchCard);
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

        if ($page->getEditorDialog()) {
            $body->append(
                $this->renderDialog($page->getEditorDialog(), $context, 'dialogForm', 'dialogVisible')
            );
        }

        $context->document()->assets()->addInlineScript($this->buildCrudRuntime($page));

        return $body;
    }

    private function renderAdminPage(AdminPage $page, RenderContext $context): AbstractHtmlElement
    {
        $body = El::double('div')->addClass('sc-v2-page');
        $body->append($this->renderPageHeader($page));

        foreach ($page->getSections() as $section) {
            $body->append($section->render($context));
        }

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

    private function renderPageHeader(AdminPage $page): AbstractHtmlElement
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

    private function renderForm(Form $form, string $modelName, array $options = []): AbstractHtmlElement
    {
        $mode = $options['mode'] ?? 'default';

        $attrs = [
            ':model' => $modelName,
            'label-width' => $form->getLabelWidth(),
        ];
        if (!empty($options['ref'])) {
            $attrs['ref'] = $options['ref'];
        }
        if (!empty($options['rules'])) {
            $attrs[':rules'] = $options['rules'];
        }

        $el = El::double('el-form')->setAttrs($attrs);

        if ($form->isInline()) {
            $el->setAttr(':inline', 'true');
            foreach ($form->fields() as $field) {
                $fieldEl = $this->renderField($field, $modelName, true, $options);
                if ($fieldEl->toHtml()) {
                    $el->append($fieldEl);
                }
            }
        } else {
            $row = El::double('el-row')->setAttr(':gutter', 16);
            foreach ($form->fields() as $field) {
                $fieldEl = $this->renderField($field, $modelName, false, $options);
                if ($fieldEl->toHtml()) {
                    $row->append($fieldEl);
                }
            }
            $el->append($row);
        }

        if ($mode === 'search') {
            $submitButton = El::double('el-button')->setAttrs([
                'type' => 'primary',
                '@click' => $options['submitMethod'] ?? 'submitSearch',
            ])->append($form->getSubmitLabel());

            $resetButton = El::double('el-button')->setAttrs([
                '@click' => $options['resetMethod'] ?? 'resetSearch',
            ])->append($form->getResetLabel());

            $actionItem = El::double('el-form-item')->setAttr('label-width', 0)
                ->append(
                    El::double('div')->addClass('sc-v2-search__actions')->append(
                        $submitButton,
                        $resetButton
                    )
                );

            if ($form->isInline()) {
                $el->append($actionItem);
            } else {
                $el->append(
                    El::double('el-row')->append(
                        El::double('el-col')->setAttr(':span', 24)->append($actionItem)
                    )
                );
            }
        }

        return $el;
    }

    private function renderField(Field $field, string $modelName, bool $inline = false, array $options = []): AbstractHtmlElement
    {
        if ($field->type() === FieldType::HIDDEN) {
            return El::fictitious();
        }

        $modelAccessor = $this->jsModelAccessor($modelName, $field->name());
        $visibleWhen = $this->normalizeFieldExpression($field->getVisibleWhen(), $modelName);
        $disabledWhen = $this->normalizeFieldExpression($field->getDisabledWhen(), $modelName);
        $optionField = $field instanceof OptionField ? $field : null;
        $uploadField = $field instanceof UploadField ? $field : null;
        $placeholderField = $field instanceof PlaceholderFieldInterface ? $field : null;
        $validatableField = $field instanceof ValidatableFieldInterface ? $field : null;
        $hasRemoteOptions = $optionField?->hasRemoteOptions()
            && !empty($options['remoteOptionsState'])
            && !empty($options['remoteLoadingState'])
            && !empty($options['remoteLoadMethod'])
            && !empty($options['remoteScope']);
        $item = El::double('el-form-item')
            ->setAttr('label', $field->label())
            ->setAttr('prop', $field->name());

        if ($validatableField?->isRequired()) {
            $item->setAttr('required');
        }

        $upload = $uploadField?->getUpload() ?? [];

        $component = match ($field->type()) {
            FieldType::TEXT => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
            ]),
            FieldType::PASSWORD => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'password',
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
            ]),
            FieldType::TEXTAREA => El::double('el-input')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'textarea',
                ':rows' => (string)($field->getProps()['rows'] ?? 4),
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
            ]),
            FieldType::NUMBER => El::double('el-input-number')->setAttrs([
                'v-model' => $modelAccessor,
                'style' => $inline ? 'width: 180px' : 'width: 100%',
            ]),
            FieldType::SELECT => El::double('el-select')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
                'style' => $inline ? 'min-width: 180px' : 'width: 100%',
            ]),
            FieldType::RADIO => El::double('el-radio-group')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            FieldType::CHECKBOX => El::double('el-checkbox-group')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            FieldType::CASCADER => El::double('el-cascader')->setAttrs([
                'v-model' => $modelAccessor,
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
                'style' => $inline ? 'min-width: 220px' : 'width: 100%',
            ]),
            FieldType::DATE => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'date',
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
                'style' => $inline ? 'width: 220px' : 'width: 100%',
            ]),
            FieldType::DATETIME => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'datetime',
                'placeholder' => $placeholderField?->getPlaceholder() ?? '',
                'clearable' => '',
                'style' => $inline ? 'width: 240px' : 'width: 100%',
            ]),
            FieldType::DATE_RANGE => El::double('el-date-picker')->setAttrs([
                'v-model' => $modelAccessor,
                'type' => 'daterange',
                'range-separator' => (string)($field->getProps()['range-separator'] ?? '至'),
                'start-placeholder' => (string)($field->getProps()['start-placeholder'] ?? '开始日期'),
                'end-placeholder' => (string)($field->getProps()['end-placeholder'] ?? '结束日期'),
                'clearable' => '',
                'style' => $inline ? 'width: 320px' : 'width: 100%',
            ]),
            FieldType::UPLOAD => El::double('el-upload')->setAttrs(array_filter([
                'action' => (string)($upload['action'] ?? ''),
                'method' => (string)($upload['method'] ?? 'post'),
                'name' => (string)($upload['name'] ?? 'file'),
                ':headers' => JsonExpressionEncoder::encode($upload['headers'] ?? []),
                ':data' => JsonExpressionEncoder::encode($upload['data'] ?? []),
                ':multiple' => ($upload['multiple'] ?? false) ? 'true' : 'false',
                ':limit' => isset($upload['limit']) ? (string)$upload['limit'] : null,
                ':show-file-list' => 'true',
                'list-type' => (string)($upload['listType'] ?? 'text'),
                'accept' => $upload['accept'] ?? '',
            ], static fn(mixed $value) => $value !== null && $value !== '')),
            FieldType::SWITCH => El::double('el-switch')->setAttrs([
                'v-model' => $modelAccessor,
            ]),
            default => El::fictitious(),
        };

        foreach ($field->getProps() as $attr => $value) {
            if ($field->type() === FieldType::TEXTAREA && $attr === 'rows') {
                continue;
            }
            if ($field->type() === FieldType::DATE_RANGE && in_array($attr, ['range-separator', 'start-placeholder', 'end-placeholder'], true)) {
                continue;
            }
            $component->setAttr($attr, (string)$value);
        }

        if ($disabledWhen !== null) {
            $component->setAttr(':disabled', $field->isDisabled() ? 'true' : $disabledWhen);
        } elseif ($field->isDisabled()) {
            $component->setAttr('disabled');
        }

        if (
            $optionField !== null
            &&
            in_array($field->type(), [FieldType::SELECT, FieldType::RADIO], true)
            && $optionField->hasLinkageUpdates()
            && !empty($options['linkageMethod'])
            && !empty($options['remoteScope'])
        ) {
            $component->setAttr(
                '@change',
                sprintf(
                    "(value) => %s('%s', '%s', value)",
                    $options['linkageMethod'],
                    $options['remoteScope'],
                    $field->name()
                )
            );
        }

        if ($field->type() === FieldType::SELECT) {
            if ($hasRemoteOptions) {
                $component->setAttr('filterable');
                $component->setAttr(
                    ':loading',
                    sprintf("%s['%s'] || false", $options['remoteLoadingState'], $field->name())
                );
                $component->setAttr(
                    '@visible-change',
                    sprintf(
                        "(visible) => visible && %s('%s', '%s')",
                        $options['remoteLoadMethod'],
                        $options['remoteScope'],
                        $field->name()
                    )
                );
            }

            $this->appendOptionChildren(
                $component,
                $optionField,
                $hasRemoteOptions
                    ? sprintf("(%s['%s'] || [])", $options['remoteOptionsState'], $field->name())
                    : null,
                'el-option'
            );
        }

        if (in_array($field->type(), [FieldType::RADIO, FieldType::CHECKBOX], true)) {
            $this->appendOptionChildren(
                $component,
                $optionField,
                $hasRemoteOptions
                    ? sprintf("(%s['%s'] || [])", $options['remoteOptionsState'], $field->name())
                    : null,
                $field->type() === FieldType::RADIO ? 'el-radio' : 'el-checkbox'
            );
        }

        if ($field->type() === FieldType::CASCADER) {
            $component->setAttr(
                ':options',
                $hasRemoteOptions
                    ? sprintf("%s['%s'] || []", $options['remoteOptionsState'], $field->name())
                    : JsonExpressionEncoder::encode($optionField?->getOptions() ?? [])
            );
        }

        if ($field->type() === FieldType::UPLOAD && $uploadField !== null) {
            if (!empty($options['uploadFilesState']) && !empty($options['uploadScope'])) {
                $successMethod = $options['uploadSuccessMethod'] ?? 'handleUploadSuccess';
                $removeMethod = $options['uploadRemoveMethod'] ?? 'handleUploadRemove';
                $exceedMethod = $options['uploadExceedMethod'] ?? 'handleUploadExceed';
                $previewMethod = $options['uploadPreviewMethod'] ?? 'handleUploadPreview';

                $component->setAttr(
                    'v-model:file-list',
                    sprintf("%s['%s']", $options['uploadFilesState'], $field->name())
                );
                $component->setAttr(
                    ':on-success',
                    sprintf(
                        "(response, uploadFile, uploadFiles) => %s('%s', '%s', response, uploadFile, uploadFiles)",
                        $successMethod,
                        $options['uploadScope'],
                        $field->name()
                    )
                );
                $component->setAttr(
                    ':on-remove',
                    sprintf(
                        "(uploadFile, uploadFiles) => %s('%s', '%s', uploadFile, uploadFiles)",
                        $removeMethod,
                        $options['uploadScope'],
                        $field->name()
                    )
                );
                $component->setAttr(
                    ':on-exceed',
                    sprintf(
                        "(files, uploadFiles) => %s('%s', '%s', files, uploadFiles)",
                        $exceedMethod,
                        $options['uploadScope'],
                        $field->name()
                    )
                );
                $component->setAttr(':on-preview', $previewMethod);
            }

            if (($upload['kind'] ?? 'file') === 'image') {
                $component->append(
                    El::double('el-icon')->append(
                        El::double('Plus')
                    )
                );
            } else {
                $component->append(
                    El::double('el-button')->setAttr('type', 'primary')->append(
                        (string)($upload['buttonText'] ?? '选择文件')
                    )
                );
            }

            if (!empty($upload['tip'])) {
                $component->append(
                    El::double('template')->setAttr('#tip')->append(
                        El::double('div')->addClass('el-upload__tip')->append((string)$upload['tip'])
                    )
                );
            }
        }

        $item->append($component);

        if ($field->getHelpText()) {
            $item->append(
                El::double('div')->addClass('sc-v2-form__help')->append($field->getHelpText())
            );
        }

        $root = $inline
            ? $item
            : El::double('el-col')->setAttr(':span', $field->getSpan())->append($item);

        if ($visibleWhen !== null) {
            $root->setAttr('v-if', $visibleWhen);
        }

        if ($inline) {
            return $root;
        }

        return $root;
    }

    private function appendOptionChildren(
        AbstractHtmlElement $component,
        ?OptionField $field,
        ?string $optionsExpression,
        string $optionTag
    ): void {
        if ($field === null) {
            return;
        }

        if ($optionsExpression !== null) {
            $option = $this->buildChoiceOptionElement($optionTag, true);
            $option->setAttrs([
                'v-for' => sprintf('(item, index) in %s', $optionsExpression),
                ':key' => 'item.value ?? index',
                ':disabled' => 'item.disabled === true',
            ]);

            if ($optionTag === 'el-option') {
                $option->setAttr(':label', 'item.label');
                $option->setAttr(':value', 'item.value');
            } else {
                $option->setAttr(':label', 'item.value');
                $option->setAttr(':value', 'item.value');
            }

            $component->append($option);

            return;
        }

        foreach ($field->getOptions() as $option) {
            $component->append($this->buildChoiceOptionElement($optionTag, false, $option));
        }
    }

    private function buildChoiceOptionElement(
        string $optionTag,
        bool $remote,
        array $option = []
    ): AbstractHtmlElement {
        $element = El::double($optionTag);

        if ($remote) {
            if ($optionTag !== 'el-option') {
                $element->append('{{ item.label }}');
            }

            return $element;
        }

        $value = $this->jsLiteral($option['value'] ?? '');
        $label = (string)($option['label'] ?? '');

        if (($option['disabled'] ?? false) === true) {
            $element->setAttr('disabled');
        }

        if ($optionTag === 'el-option') {
            $element->setAttrs([
                'label' => $label,
                ':value' => $value,
            ]);

            return $element;
        }

        $element->setAttrs([
            ':label' => $value,
            ':value' => $value,
        ])->append($label);

        return $element;
    }

    private function renderTableToolbar(Table $table): AbstractHtmlElement
    {
        $toolbar = El::double('div')->addClass('sc-v2-toolbar');
        $left = El::double('div')->addClass('sc-v2-toolbar__actions');

        foreach ($table->getToolbarActions() as $action) {
            $left->append($this->renderActionButton($action));
        }

        $toolbar->append($left);

        return $toolbar;
    }

    private function renderTable(Table $table, string $rowsName = 'tableRows', string $loadingName = 'tableLoading'): AbstractHtmlElement
    {
        $el = El::double('el-table')->setAttrs([
            ':data' => $rowsName,
            'v-loading' => $loadingName,
            ':stripe' => $table->useStripe() ? 'true' : 'false',
            ':border' => $table->useBorder() ? 'true' : 'false',
            'empty-text' => $table->getEmptyText(),
            'style' => 'width: 100%',
            '@sort-change' => 'handleSortChange',
        ]);

        foreach ($table->columns() as $column) {
            $el->append($this->renderColumn($column));
        }

        if ($table->getRowActions()) {
            $actionColumn = El::double('el-table-column')->setAttrs([
                'label' => '操作',
                'fixed' => 'right',
                'width' => max(120, count($table->getRowActions()) * 76),
            ]);

            $template = El::double('template')->setAttr('#default', 'scope');
            $actions = El::double('div')->addClass('sc-v2-row-actions');
            foreach ($table->getRowActions() as $action) {
                $actions->append($this->renderActionButton($action, true, 'small'));
            }
            $template->append($actions);
            $actionColumn->append($template);
            $el->append($actionColumn);
        }

        return $el;
    }

    private function renderPagination(Table $table): AbstractHtmlElement
    {
        return El::double('div')->setAttr('style', 'display:flex;justify-content:flex-end')
            ->append(
                El::double('el-pagination')->setAttrs([
                    'background' => '',
                    'layout' => 'total, sizes, prev, pager, next, jumper',
                    ':current-page' => 'tablePage',
                    ':page-size' => 'tablePageSize',
                    ':page-sizes' => JsonExpressionEncoder::encode($table->getPageSizes()),
                    ':total' => 'tableTotal',
                    '@size-change' => 'handlePageSizeChange',
                    '@current-change' => 'handlePageChange',
                ])
            );
    }

    private function renderColumn(Column $column): AbstractHtmlElement
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

        $el = El::double('el-table-column')->setAttrs($attrs);

        if ($column->getFormat()) {
            $template = El::double('template')->setAttr('#default', 'scope');
            $format = trim($column->getFormat());
            $template->append(str_starts_with($format, '<') ? El::fromCode($format) : $format);
            $el->append($template);

            return $el;
        }

        if ($column->getDisplay()) {
            $el->append($this->renderColumnDisplayTemplate($column));

            return $el;
        }

        $el->append($this->renderPlainColumnTemplate($column));

        return $el;
    }

    private function renderPlainColumnTemplate(Column $column): AbstractHtmlElement
    {
        $template = El::double('template')->setAttr('#default', 'scope');
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $displayExpression = sprintf(
            "Array.isArray(%s) ? %s.join(', ') : %s",
            $valueExpression,
            $valueExpression,
            $valueExpression
        );

        $template->append(
            El::double('span')
                ->setAttr('v-if', '!(' . $this->jsBlankCheck($displayExpression) . ')')
                ->append('{{ ' . $displayExpression . ' }}'),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderColumnDisplayTemplate(Column $column): AbstractHtmlElement
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

    private function renderMappingColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $options = JsonExpressionEncoder::encode(array_values($display['options'] ?? []));
        $separator = $this->jsLiteral($display['separator'] ?? ', ');
        $labelExpression = sprintf(
            "Array.isArray(%s) ? %s.map((value) => ((%s).find((item) => item.value == value)?.label ?? '')).filter((value) => value !== '').join(%s) : (((%s).find((item) => item.value == %s)?.label) ?? '')",
            $valueExpression,
            $valueExpression,
            $options,
            $separator,
            $options,
            $valueExpression
        );

        $template->append(
            El::double('span')
                ->setAttr('v-if', '!(' . $this->jsBlankCheck($labelExpression) . ')')
                ->append('{{ ' . $labelExpression . ' }}'),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderTagColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $options = JsonExpressionEncoder::encode(array_values($display['options'] ?? []));
        $labelExpression = sprintf(
            "((%s).find((item) => item.value == %s)?.label) ?? ''",
            $options,
            $valueExpression
        );
        $typeExpression = sprintf(
            "((%s).find((item) => item.value == %s)?.type) ?? %s",
            $options,
            $valueExpression,
            $this->jsLiteral($display['defaultType'] ?? 'info')
        );

        $template->append(
            El::double('el-tag')
                ->setAttr('v-if', '!(' . $this->jsBlankCheck($labelExpression) . ')')
                ->setAttr(':type', $typeExpression)
                ->append('{{ ' . $labelExpression . ' }}'),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

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
                'v-if' => '!(' . $this->jsBlankCheck($valueExpression) . ')',
                ':src' => $valueExpression,
                ':preview-src-list' => '[' . $valueExpression . ']',
                ':preview-teleported' => 'true',
                'fit' => (string)($display['fit'] ?? 'cover'),
                'style' => $style,
                'hide-on-click-modal' => '',
            ]),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
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
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderBooleanColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $truthyCheck = $this->jsTruthyValueCheck($valueExpression);
        $falsyCheck = $this->jsFalsyValueCheck($valueExpression);

        $template->append(
            El::double('span')
                ->setAttr('v-if', $truthyCheck)
                ->append((string)($display['truthyLabel'] ?? '是')),
            El::double('span')
                ->setAttr('v-else-if', $falsyCheck)
                ->append((string)($display['falsyLabel'] ?? '否')),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderBooleanTagColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $truthyCheck = $this->jsTruthyValueCheck($valueExpression);
        $falsyCheck = $this->jsFalsyValueCheck($valueExpression);

        $template->append(
            El::double('el-tag')
                ->setAttr('v-if', $truthyCheck)
                ->setAttr('type', (string)($display['truthyType'] ?? 'success'))
                ->append((string)($display['truthyLabel'] ?? '是')),
            El::double('el-tag')
                ->setAttr('v-else-if', $falsyCheck)
                ->setAttr('type', (string)($display['falsyType'] ?? 'info'))
                ->append((string)($display['falsyLabel'] ?? '否')),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderDatetimeColumnTemplate(AbstractHtmlElement $template, Column $column, array $display): AbstractHtmlElement
    {
        $valueExpression = $this->jsReadableAccessor('scope.row', $column->prop());
        $formatExpression = $this->jsDateFormatExpression($valueExpression, (string)($display['format'] ?? 'YYYY-MM-DD HH:mm:ss'));

        $template->append(
            El::double('span')
                ->setAttr('v-if', '!(' . $this->jsBlankCheck($valueExpression) . ')')
                ->append('{{ ' . $formatExpression . ' }}'),
            El::double('span')
                ->setAttr('v-else')
                ->append($column->getPlaceholder())
        );

        return $template;
    }

    private function renderDialog(Dialog $dialog, RenderContext $context, string $formModel, string $visibleModel): AbstractHtmlElement
    {
        $el = El::double('el-dialog')->setAttrs([
            'v-model' => $visibleModel,
            'title' => $dialog->title(),
            'width' => $dialog->getWidth(),
            'destroy-on-close' => '',
        ]);

        if ($dialog->getForm()) {
            $el->append($this->renderForm($dialog->getForm(), $formModel, [
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
            ]));
        }

        $footer = El::double('template')->setAttr('#footer');
        $footerActions = $dialog->getFooterActions();
        if (!$footerActions) {
            $footerActions = [
                Action::close('取消', $dialog->key()),
                Action::submit('保存', $dialog->key()),
            ];
        }

        foreach ($footerActions as $action) {
            $footer->append($this->renderActionButton($action));
        }

        $el->append($footer);

        return $el;
    }

    private function renderActionButton(Action $action, bool $rowScoped = false, string $size = 'default'): AbstractHtmlElement
    {
        $attrs = array_merge([
            'type' => $action->buttonType(),
            'size' => $size,
        ], $action->attrs());

        if ($action->intent() === ActionIntent::REFRESH) {
            $attrs[':loading'] = 'tableLoading';
        }

        if ($action->intent() === ActionIntent::SUBMIT) {
            $attrs[':loading'] = 'dialogSubmitting';
        }

        if ($action->intent() === ActionIntent::CLOSE) {
            $attrs[':disabled'] = 'dialogSubmitting';
        }

        $click = $this->resolveActionClick($action, $rowScoped);
        if ($click) {
            $attrs['@click'] = $click;
        }

        $button = El::double('el-button')->setAttrs($attrs);

        if ($action->iconName()) {
            $button->append(
                El::double('el-icon')->append(
                    El::double($action->iconName())
                )
            );
        }

        $button->append($action->label());

        return $button;
    }

    private function resolveActionClick(Action $action, bool $rowScoped): ?string
    {
        return match ($action->intent()) {
            ActionIntent::CREATE => sprintf("openDialog('%s')", $action->targetName() ?: 'editor'),
            ActionIntent::EDIT => sprintf("openDialog('%s', %s)", $action->targetName() ?: 'editor', $rowScoped ? 'scope.row' : 'null'),
            ActionIntent::DELETE => $rowScoped ? 'deleteRow(scope.row)' : 'deleteRow()',
            ActionIntent::SUBMIT => sprintf("submitDialog('%s')", $action->targetName() ?: 'editor'),
            ActionIntent::CLOSE => sprintf("closeDialog('%s')", $action->targetName() ?: 'editor'),
            ActionIntent::REFRESH => 'loadTableData()',
            ActionIntent::CUSTOM => $action->handler() instanceof \Stringable ? (string)$action->handler() : $action->handler(),
        };
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
        if ($context->get('v2.simple.runtime')) {
            return;
        }

        $state = JsonExpressionEncoder::encode($context->get('v2.simple.state', []));
        $config = JsonExpressionEncoder::encode($context->get('v2.simple.config', []));
        $context->document()->assets()->addInlineScript(<<<JS
        (function(state, cfg){
          const isObject = (value) => value && typeof value === 'object' && !Array.isArray(value);
          const clone = (value) => {
            if (Array.isArray(value)) {
              return value.map((item) => clone(item));
            }
            if (value instanceof RegExp) {
              return new RegExp(value.source, value.flags);
            }
            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = clone(value[key]);
              });
              return output;
            }
            return value;
          };
          const isBlank = (value) => value === '' || value === null || value === undefined || (Array.isArray(value) && value.length === 0);
          const isRowArray = (value) => {
            if (!Array.isArray(value)) return false;
            if (value.length === 0) return true;
            return typeof value[0] === 'object' || Array.isArray(value[0]);
          };
          const getByPath = (source, path) => {
            if (!path) return undefined;
            return String(path)
              .split('.')
              .reduce((current, segment) => current == null ? undefined : current[segment], source);
          };
          const setByPath = (source, path, value) => {
            if (!isObject(source) || !path) return;

            const segments = String(path).split('.').filter(Boolean);
            if (segments.length === 0) return;

            let current = source;
            segments.slice(0, -1).forEach((segment) => {
              if (!isObject(current[segment])) {
                current[segment] = {};
              }
              current = current[segment];
            });

            current[segments[segments.length - 1]] = value;
          };
          const extractPayload = (response) => {
            if (response && typeof response === 'object' && Object.prototype.hasOwnProperty.call(response, 'data')) {
              return response.data;
            }
            return response;
          };
          const resolveMessage = (payload, fallback = '') => {
            if (typeof payload === 'string' && payload !== '') return payload;
            if (!isObject(payload)) return fallback;
            return payload.message || payload.msg || payload.error || fallback;
          };
          const isSuccessPayload = (payload) => {
            if (!isObject(payload)) return true;
            if (typeof payload.success === 'boolean') return payload.success;
            if (payload.code !== undefined) return [0, 200, '0', '200'].includes(payload.code);
            if (payload.status !== undefined) {
              if (typeof payload.status === 'number') {
                return payload.status >= 200 && payload.status < 300;
              }
              return ['success', 'ok'].includes(String(payload.status).toLowerCase());
            }
            return true;
          };
          const ensureSuccess = (payload, fallback) => {
            if (isSuccessPayload(payload)) {
              return payload;
            }
            throw new Error(resolveMessage(payload, fallback));
          };
          const pickRows = (payload, depth = 0) => {
            if (depth > 4) return [];
            if (isRowArray(payload)) return payload;
            if (!isObject(payload)) return [];

            const directKeys = ['data', 'rows', 'list', 'items', 'records'];
            for (const key of directKeys) {
              if (isRowArray(payload[key])) return payload[key];
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const found = pickRows(payload[key], depth + 1);
                if (found.length > 0 || isRowArray(payload[key])) return found;
              }
            }

            return [];
          };
          const makeRequest = (request) => {
            const method = (request?.method || 'GET').toLowerCase();
            if (method === 'get') {
              return axios.get(request.url, { params: request.query || {} });
            }
            return axios({ method, url: request.url, data: request.query || {} });
          };
          const normalizeOption = (item, fieldCfg, index) => {
            if (!isObject(item)) {
              return {
                value: item,
                label: item == null ? '' : String(item),
                disabled: false
              };
            }

            const value = item.value !== undefined
              ? item.value
              : getByPath(item, fieldCfg?.valueField || 'value');
            const label = item.label !== undefined
              ? item.label
              : getByPath(item, fieldCfg?.labelField || 'label');

            return Object.assign({}, item, {
              value: value ?? index,
              label: label ?? String(value ?? ''),
              disabled: item.disabled === true
            });
          };
          const normalizeDependencies = (fieldCfg) => {
            return Array.from(new Set(
              Array.isArray(fieldCfg?.dependencies)
                ? fieldCfg.dependencies.filter((item) => typeof item === 'string' && item !== '')
                : []
            ));
          };
          const resolveDynamicParams = (params, model) => {
            const query = {};

            Object.keys(params || {}).forEach((key) => {
              const value = params[key];
              if (typeof value === 'string' && value.startsWith('@')) {
                const resolved = getByPath(model, value.slice(1));
                if (!isBlank(resolved)) {
                  query[key] = resolved;
                }
                return;
              }

              if (value !== undefined) {
                query[key] = value;
              }
            });

            return query;
          };
          const hasReadyDependencies = (fieldCfg, model) => {
            const dependencies = normalizeDependencies(fieldCfg);
            if (dependencies.length === 0) {
              return true;
            }

            return dependencies.every((path) => !isBlank(getByPath(model, path)));
          };
          const isSameValue = (left, right) => {
            if (left === right) return true;
            if (left === null || left === undefined || right === null || right === undefined) {
              return false;
            }

            return String(left) === String(right);
          };
          const resolveLinkageToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') return '';
            if (path === 'value') return context.value;
            if (path === 'label') return context.option?.label ?? '';
            if (path.startsWith('model.')) return getByPath(context.model, path.slice(6));
            if (path.startsWith('option.')) return getByPath(context.option, path.slice(7));

            return getByPath(context.option, path);
          };
          const resolveLinkageTemplate = (template, context) => {
            if (typeof template === 'function') {
              return template(context);
            }
            if (template === null || template === undefined) {
              return '';
            }
            if (typeof template !== 'string') {
              return template;
            }
            if (/^@[\w.]+$/.test(template)) {
              return resolveLinkageToken(template, context);
            }

            return template.replace(/@[\w.]+/g, (token) => {
              const value = resolveLinkageToken(token, context);
              return value === null || value === undefined ? '' : String(value);
            });
          };
          const extractFileName = (url, fallback = 'file') => {
            if (typeof url !== 'string' || url === '') {
              return fallback;
            }

            const clean = url.split('?')[0].split('#')[0];
            const parts = clean.split('/').filter(Boolean);

            return parts[parts.length - 1] || fallback;
          };
          const resolveUploadValue = (payload, fieldCfg, depth = 0) => {
            if (depth > 4 || payload === null || payload === undefined) {
              return null;
            }
            if (typeof payload === 'string') {
              return payload;
            }
            if (!isObject(payload)) {
              return null;
            }

            if (fieldCfg?.responsePath) {
              const pathValue = getByPath(payload, fieldCfg.responsePath);
              if (!isBlank(pathValue)) {
                return pathValue;
              }
            }

            const directKeys = ['url', 'path', 'value', 'src'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'string' && payload[key] !== '') {
                return payload[key];
              }
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const resolved = resolveUploadValue(payload[key], fieldCfg, depth + 1);
                if (!isBlank(resolved)) {
                  return resolved;
                }
              }
            }

            return null;
          };
          const normalizeUploadFile = (item, fieldCfg, index) => {
            if (typeof item === 'string') {
              return {
                uid: 'init-' + index,
                name: extractFileName(item, 'file-' + (index + 1)),
                url: item,
                responseValue: item,
                status: 'success'
              };
            }
            if (!isObject(item)) {
              return null;
            }

            const responseValue = item.responseValue
              ?? resolveUploadValue(item.response, fieldCfg)
              ?? resolveUploadValue(item, fieldCfg);
            const url = item.url || item.value || item.src || responseValue;
            if (isBlank(url)) {
              return null;
            }

            return Object.assign({}, item, {
              uid: item.uid || ('file-' + index),
              name: item.name || extractFileName(String(url), 'file-' + (index + 1)),
              url,
              responseValue: responseValue || url,
              status: item.status || 'success'
            });
          };
          const normalizeUploadFiles = (value, fieldCfg) => {
            const source = Array.isArray(value)
              ? value
              : (isBlank(value) ? [] : [value]);
            const files = source
              .map((item, index) => normalizeUploadFile(item, fieldCfg, index))
              .filter(Boolean);

            return fieldCfg?.multiple ? files : files.slice(0, 1);
          };
          const syncUploadModelValue = (model, fieldName, fieldCfg, files) => {
            const normalized = normalizeUploadFiles(files, fieldCfg);
            const values = normalized
              .map((file) => file.responseValue || file.url)
              .filter((value) => !isBlank(value));

            setByPath(model, fieldName, fieldCfg?.multiple ? values : (values[0] ?? ''));

            return normalized;
          };
          const app = Vue.createApp({
            data(){
              return state || {};
            },
            mounted(){
              Object.keys(cfg?.forms || {}).forEach((scope) => {
                this.registerSimpleFormDependencies(scope);
                this.initializeSimpleFormOptions(scope);
                this.initializeSimpleUploadFiles(scope);
              });
            },
            methods:{
              getSimpleFormConfig(scope){
                return cfg?.forms?.[scope] || null;
              },
              getSimpleFormRef(scope){
                const refName = this.getSimpleFormConfig(scope)?.ref;
                if (!refName) return null;

                const formRef = this.\$refs[refName];
                return Array.isArray(formRef) ? formRef[0] : formRef;
              },
              validateSimpleForm(scope){
                const formRef = this.getSimpleFormRef(scope);
                if (!formRef || typeof formRef.validate !== 'function') {
                  return Promise.resolve(true);
                }

                try {
                  const result = formRef.validate();
                  if (result && typeof result.then === 'function') {
                    return result.then(() => true).catch(() => false);
                  }
                } catch (error) {
                  return Promise.resolve(false);
                }

                return Promise.resolve(true);
              },
              clearSimpleFormValidate(scope){
                const formRef = this.getSimpleFormRef(scope);
                if (formRef && typeof formRef.clearValidate === 'function') {
                  formRef.clearValidate();
                }
              },
              getSimpleFormModel(scope){
                const config = this.getSimpleFormConfig(scope);
                return config?.modelVar ? (this[config.modelVar] || {}) : {};
              },
              getSimpleOptionState(scope){
                const config = this.getSimpleFormConfig(scope);
                if (!config?.optionStateVar) return {};
                this[config.optionStateVar] ??= {};
                return this[config.optionStateVar];
              },
              getSimpleOptionLoadingState(scope){
                const config = this.getSimpleFormConfig(scope);
                if (!config?.optionLoadingVar) return {};
                this[config.optionLoadingVar] ??= {};
                return this[config.optionLoadingVar];
              },
              getSimpleOptionLoadedState(scope){
                const config = this.getSimpleFormConfig(scope);
                if (!config?.optionLoadedVar) return {};
                this[config.optionLoadedVar] ??= {};
                return this[config.optionLoadedVar];
              },
              getSimpleUploadFileState(scope){
                const config = this.getSimpleFormConfig(scope);
                if (!config?.uploadFilesVar) return {};
                this[config.uploadFilesVar] ??= {};
                return this[config.uploadFilesVar];
              },
              getSimpleFieldOptions(scope, fieldName){
                const formCfg = this.getSimpleFormConfig(scope);
                if (formCfg?.remoteOptions?.[fieldName]) {
                  return this.getSimpleOptionState(scope)[fieldName] || [];
                }

                return (formCfg?.selectOptions?.[fieldName] || []).map((item, index) => normalizeOption(item, {}, index));
              },
              getSimpleLinkageConfig(scope, fieldName){
                return this.getSimpleFormConfig(scope)?.linkages?.[fieldName] || null;
              },
              clearSimpleLinkageTargets(scope, fieldName){
                const linkCfg = this.getSimpleLinkageConfig(scope, fieldName);
                if (!linkCfg?.updates) {
                  return;
                }

                const model = this.getSimpleFormModel(scope);
                Object.keys(linkCfg.updates).forEach((targetField) => {
                  const currentValue = getByPath(model, targetField);
                  setByPath(model, targetField, Array.isArray(currentValue) ? [] : '');
                });
              },
              applySimpleFormLinkage(scope, fieldName, value){
                const linkCfg = this.getSimpleLinkageConfig(scope, fieldName);
                if (!linkCfg?.updates) {
                  return;
                }

                const model = this.getSimpleFormModel(scope);
                const currentValue = value ?? getByPath(model, fieldName);
                if (isBlank(currentValue)) {
                  if (linkCfg.clearOnEmpty !== false) {
                    this.clearSimpleLinkageTargets(scope, fieldName);
                  }
                  return;
                }

                const option = this.getSimpleFieldOptions(scope, fieldName)
                  .find((item) => isSameValue(item?.value, currentValue));
                if (!option) {
                  return;
                }

                const context = {
                  scope,
                  fieldName,
                  value: currentValue,
                  option,
                  model
                };

                Object.keys(linkCfg.updates).forEach((targetField) => {
                  setByPath(model, targetField, resolveLinkageTemplate(linkCfg.updates[targetField], context));
                });
              },
              nextSimpleRemoteRequestToken(scope, fieldName){
                this.__simpleRemoteRequestTokens ??= {};
                const key = scope + ':' + fieldName;
                const token = (this.__simpleRemoteRequestTokens[key] || 0) + 1;
                this.__simpleRemoteRequestTokens[key] = token;

                return token;
              },
              isLatestSimpleRemoteRequestToken(scope, fieldName, token){
                const key = scope + ':' + fieldName;

                return (this.__simpleRemoteRequestTokens?.[key] || 0) === token;
              },
              resetSimpleRemoteFieldState(scope, fieldName, clearValue = false){
                this.nextSimpleRemoteRequestToken(scope, fieldName);
                this.getSimpleOptionState(scope)[fieldName] = [];
                this.getSimpleOptionLoadingState(scope)[fieldName] = false;
                this.getSimpleOptionLoadedState(scope)[fieldName] = false;

                if (!clearValue) {
                  return;
                }

                const model = this.getSimpleFormModel(scope);
                const currentValue = getByPath(model, fieldName);
                setByPath(model, fieldName, Array.isArray(currentValue) ? [] : '');
              },
              registerSimpleFormDependencies(scope){
                const formCfg = this.getSimpleFormConfig(scope);
                const configMap = formCfg?.remoteOptions || {};

                Object.keys(configMap).forEach((fieldName) => {
                  const fieldCfg = configMap[fieldName] || {};
                  const dependencies = normalizeDependencies(fieldCfg);
                  if (dependencies.length === 0) {
                    return;
                  }

                  this.\$watch(
                    () => JSON.stringify(dependencies.map((path) => getByPath(this.getSimpleFormModel(scope), path))),
                    () => {
                      const shouldClear = fieldCfg.clearOnChange !== false;
                      this.reloadSimpleDependentFieldOptions(scope, fieldName, shouldClear);
                    }
                  );
                });
              },
              reloadSimpleDependentFieldOptions(scope, fieldName, clearValue = true){
                this.resetSimpleRemoteFieldState(scope, fieldName, clearValue);

                return this.loadSimpleFormFieldOptions(scope, fieldName, true);
              },
              initializeSimpleFormOptions(scope, force = false){
                const formCfg = this.getSimpleFormConfig(scope);
                const configMap = formCfg?.remoteOptions || {};

                Object.keys(configMap).forEach((fieldName) => {
                  this.loadSimpleFormFieldOptions(scope, fieldName, force);
                });
              },
              loadSimpleFormFieldOptions(scope, fieldName, force = false){
                const formCfg = this.getSimpleFormConfig(scope);
                const fieldCfg = formCfg?.remoteOptions?.[fieldName];
                if (!fieldCfg?.url) {
                  return Promise.resolve([]);
                }

                const model = this.getSimpleFormModel(scope);
                if (!hasReadyDependencies(fieldCfg, model)) {
                  this.resetSimpleRemoteFieldState(scope, fieldName);
                  return Promise.resolve([]);
                }

                const loadingState = this.getSimpleOptionLoadingState(scope);
                const loadedState = this.getSimpleOptionLoadedState(scope);
                if (loadingState[fieldName]) {
                  return Promise.resolve(this.getSimpleOptionState(scope)[fieldName] || []);
                }
                if (!force && loadedState[fieldName]) {
                  return Promise.resolve(this.getSimpleOptionState(scope)[fieldName] || []);
                }

                const requestToken = this.nextSimpleRemoteRequestToken(scope, fieldName);
                loadingState[fieldName] = true;

                return makeRequest({
                  method: fieldCfg.method || 'get',
                  url: fieldCfg.url,
                  query: Object.assign({}, resolveDynamicParams(fieldCfg.params || {}, model))
                })
                  .then((response) => {
                    if (!this.isLatestSimpleRemoteRequestToken(scope, fieldName, requestToken)) {
                      return this.getSimpleOptionState(scope)[fieldName] || [];
                    }

                    const payload = ensureSuccess(extractPayload(response), '选项加载失败');
                    const options = pickRows(payload).map((item, index) => normalizeOption(item, fieldCfg, index));
                    this.getSimpleOptionState(scope)[fieldName] = options;
                    loadedState[fieldName] = true;

                    return options;
                  })
                  .catch((error) => {
                    if (!this.isLatestSimpleRemoteRequestToken(scope, fieldName, requestToken)) {
                      return [];
                    }

                    loadedState[fieldName] = false;
                    const message = error?.message || resolveMessage(error?.response?.data, '选项加载失败');
                    ElementPlus.ElMessage.error(message);
                    return [];
                  })
                  .finally(() => {
                    if (this.isLatestSimpleRemoteRequestToken(scope, fieldName, requestToken)) {
                      loadingState[fieldName] = false;
                    }
                  });
              },
              initializeSimpleUploadFiles(scope){
                const formCfg = this.getSimpleFormConfig(scope);
                const uploadConfigs = formCfg?.uploads || {};
                const model = this.getSimpleFormModel(scope);
                const state = this.getSimpleUploadFileState(scope);

                Object.keys(uploadConfigs).forEach((fieldName) => {
                  state[fieldName] = normalizeUploadFiles(getByPath(model, fieldName), uploadConfigs[fieldName] || {});
                });
              },
              handleSimpleUploadSuccess(scope, fieldName, response, uploadFile, uploadFiles){
                const formCfg = this.getSimpleFormConfig(scope);
                const fieldCfg = formCfg?.uploads?.[fieldName] || {};

                try {
                  const payload = ensureSuccess(response, '上传失败');
                  const storedValue = resolveUploadValue(payload, fieldCfg);
                  if (isBlank(storedValue)) {
                    throw new Error(resolveMessage(payload, '上传返回数据无效'));
                  }

                  const nextFiles = normalizeUploadFiles(
                    (uploadFiles || []).map((file) => {
                      if (file.uid === uploadFile.uid) {
                        return Object.assign({}, file, {
                          url: typeof storedValue === 'string' ? storedValue : (file.url || ''),
                          responseValue: storedValue
                        });
                      }

                      return file;
                    }),
                    fieldCfg
                  );

                  this.getSimpleUploadFileState(scope)[fieldName] = nextFiles;
                  syncUploadModelValue(this.getSimpleFormModel(scope), fieldName, fieldCfg, nextFiles);
                  ElementPlus.ElMessage.success(resolveMessage(payload, '上传成功'));
                } catch (error) {
                  const nextFiles = normalizeUploadFiles(
                    (uploadFiles || []).filter((file) => file.uid !== uploadFile.uid),
                    fieldCfg
                  );
                  this.getSimpleUploadFileState(scope)[fieldName] = nextFiles;
                  syncUploadModelValue(this.getSimpleFormModel(scope), fieldName, fieldCfg, nextFiles);
                  ElementPlus.ElMessage.error(error?.message || '上传失败');
                }
              },
              handleSimpleUploadRemove(scope, fieldName, uploadFile, uploadFiles){
                const formCfg = this.getSimpleFormConfig(scope);
                const fieldCfg = formCfg?.uploads?.[fieldName] || {};
                const nextFiles = normalizeUploadFiles(uploadFiles || [], fieldCfg);
                this.getSimpleUploadFileState(scope)[fieldName] = nextFiles;
                syncUploadModelValue(this.getSimpleFormModel(scope), fieldName, fieldCfg, nextFiles);
              },
              handleSimpleUploadExceed(scope, fieldName, files, uploadFiles){
                const formCfg = this.getSimpleFormConfig(scope);
                const fieldCfg = formCfg?.uploads?.[fieldName] || {};
                const limit = fieldCfg.limit || 1;
                ElementPlus.ElMessage.error('最多只能上传 ' + limit + ' 个文件');
              },
              handleSimpleUploadPreview(uploadFile){
                const url = uploadFile?.url
                  || uploadFile?.responseValue
                  || resolveUploadValue(uploadFile?.response || uploadFile, {});

                if (isBlank(url)) {
                  return;
                }

                window.open(String(url), '_blank');
              }
            }
          });
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          app.mount('#app');
        })($state, $config);
        JS);
        $context->set('v2.simple.runtime', true);
    }

    private function buildCrudRuntime(CrudPage $page): string
    {
        $table = $page->getTable();
        $dataSource = $table?->getDataSource();

        $config = JsonExpressionEncoder::encode([
            'title' => $page->title(),
            'searchDefaults' => $page->getSearchForm()?->defaults() ?? [],
            'searchSchema' => $page->getSearchForm()?->searchSchema() ?? [],
            'searchRules' => $page->getSearchForm()?->rules() ?? [],
            'searchRemoteOptions' => $page->getSearchForm()?->remoteOptions() ?? [],
            'searchSelectOptions' => $page->getSearchForm()?->selectOptions() ?? [],
            'searchLinkages' => $page->getSearchForm()?->linkages() ?? [],
            'searchUploads' => $page->getSearchForm()?->uploads() ?? [],
            'dialogDefaults' => $page->getEditorDialog()?->getForm()?->defaults() ?? [],
            'dialogRules' => $page->getEditorDialog()?->getForm()?->rules() ?? [],
            'dialogRemoteOptions' => $page->getEditorDialog()?->getForm()?->remoteOptions() ?? [],
            'dialogSelectOptions' => $page->getEditorDialog()?->getForm()?->selectOptions() ?? [],
            'dialogLinkages' => $page->getEditorDialog()?->getForm()?->linkages() ?? [],
            'dialogUploads' => $page->getEditorDialog()?->getForm()?->uploads() ?? [],
            'initialRows' => $dataSource?->initialRows() ?? [],
            'list' => $dataSource?->toClientConfig(),
            'saveUrl' => $page->getSaveUrl(),
            'deleteUrl' => $page->getDeleteUrl(),
            'deleteKey' => $page->getDeleteKey(),
            'pagination' => [
                'enabled' => $table?->usePagination() ?? false,
                'pageSize' => $table?->getPageSize() ?? 20,
                'pageSizes' => $table?->getPageSizes() ?? [10, 20, 50, 100],
            ],
            'sortFieldMap' => $this->getSortFieldMap($table),
        ]);

        return <<<JS
        (function(cfg){
          const isObject = (value) => value && typeof value === 'object' && !Array.isArray(value);
          const clone = (value) => {
            if (Array.isArray(value)) {
              return value.map((item) => clone(item));
            }
            if (value instanceof RegExp) {
              return new RegExp(value.source, value.flags);
            }
            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = clone(value[key]);
              });
              return output;
            }
            return value;
          };
          const isRowArray = (value) => {
            if (!Array.isArray(value)) return false;
            if (value.length === 0) return true;
            return typeof value[0] === 'object' || Array.isArray(value[0]);
          };
          const getByPath = (source, path) => {
            if (!path) return undefined;
            return String(path)
              .split('.')
              .reduce((current, segment) => current == null ? undefined : current[segment], source);
          };
          const extractPayload = (response) => {
            if (response && typeof response === 'object' && Object.prototype.hasOwnProperty.call(response, 'data')) {
              return response.data;
            }
            return response;
          };
          const resolveMessage = (payload, fallback = '') => {
            if (typeof payload === 'string' && payload !== '') return payload;
            if (!isObject(payload)) return fallback;
            return payload.message || payload.msg || payload.error || fallback;
          };
          const isSuccessPayload = (payload) => {
            if (!isObject(payload)) return true;
            if (typeof payload.success === 'boolean') return payload.success;
            if (payload.code !== undefined) return [0, 200, '0', '200'].includes(payload.code);
            if (payload.status !== undefined) {
              if (typeof payload.status === 'number') {
                return payload.status >= 200 && payload.status < 300;
              }
              return ['success', 'ok'].includes(String(payload.status).toLowerCase());
            }
            return true;
          };
          const ensureSuccess = (payload, fallback) => {
            if (isSuccessPayload(payload)) {
              return payload;
            }
            throw new Error(resolveMessage(payload, fallback));
          };
          const pickRows = (payload, depth = 0) => {
            if (depth > 4) return [];
            if (isRowArray(payload)) return payload;
            if (!isObject(payload)) return [];

            const directKeys = ['data', 'rows', 'list', 'items', 'records'];
            for (const key of directKeys) {
              if (isRowArray(payload[key])) return payload[key];
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const found = pickRows(payload[key], depth + 1);
                if (found.length > 0 || isRowArray(payload[key])) return found;
              }
            }

            return [];
          };
          const pickTotal = (payload, depth = 0) => {
            if (depth > 4 || !isObject(payload)) return null;
            const directKeys = ['total', 'count'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'number') return payload[key];
            }
            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const total = pickTotal(payload[key], depth + 1);
                if (typeof total === 'number') return total;
              }
            }
            return null;
          };
          const makeRequest = (request) => {
            const method = (request?.method || 'GET').toLowerCase();
            if (method === 'get') {
              return axios.get(request.url, { params: request.query || {} });
            }
            return axios({ method, url: request.url, data: request.query || {} });
          };
          const isBlank = (value) => value === '' || value === null || value === undefined || (Array.isArray(value) && value.length === 0);
          const normalizeSearchValue = (value, type) => {
            if (type === 'BETWEEN' && Array.isArray(value)) return value;
            return value;
          };
          const buildSearchQuery = (model, schema) => {
            const search = {};
            const searchType = {};
            const searchField = {};
            Object.keys(schema || {}).forEach((key) => {
              const value = model[key];
              if (isBlank(value)) return;
              const meta = schema[key] || {};
              search[key] = normalizeSearchValue(value, meta.type || '=');
              searchType[key] = meta.type || '=';
              if (meta.field) {
                searchField[key] = meta.field;
              }
            });
            if (Object.keys(search).length === 0) {
              return {};
            }
            return {
              search: {
                search,
                searchType,
                searchField,
              }
            };
          };
          const compareValues = (left, right, order) => {
            const modifier = order === 'descending' ? -1 : 1;
            if (left === right) return 0;
            if (left === null || left === undefined) return -1 * modifier;
            if (right === null || right === undefined) return 1 * modifier;
            if (typeof left === 'number' && typeof right === 'number') {
              return (left - right) * modifier;
            }
            return String(left).localeCompare(String(right), 'zh-CN') * modifier;
          };
          const applyLocalSearch = (rows, model, schema) => {
            return rows.filter((row) => {
              return Object.keys(schema || {}).every((key) => {
                const value = model[key];
                if (isBlank(value)) return true;
                const meta = schema[key] || {};
                const type = String(meta.type || '=').toUpperCase();
                const rowValue = row[key];
                if (type === 'LIKE') {
                  return String(rowValue ?? '').includes(String(value));
                }
                if (type === 'LIKE_RIGHT') {
                  return String(rowValue ?? '').startsWith(String(value));
                }
                if (type === 'IN') {
                  return Array.isArray(value) ? value.includes(rowValue) : false;
                }
                if (type === 'BETWEEN') {
                  return Array.isArray(value) && value.length === 2
                    ? rowValue >= value[0] && rowValue <= value[1]
                    : true;
                }
                return String(rowValue ?? '') === String(value);
              });
            });
          };
          const normalizeOption = (item, fieldCfg, index) => {
            if (!isObject(item)) {
              return {
                value: item,
                label: item == null ? '' : String(item),
                disabled: false
              };
            }

            const value = item.value !== undefined
              ? item.value
              : getByPath(item, fieldCfg?.valueField || 'value');
            const label = item.label !== undefined
              ? item.label
              : getByPath(item, fieldCfg?.labelField || 'label');

            return Object.assign({}, item, {
              value: value ?? index,
              label: label ?? String(value ?? ''),
              disabled: item.disabled === true
            });
          };
          const normalizeDependencies = (fieldCfg) => {
            return Array.from(new Set(
              Array.isArray(fieldCfg?.dependencies)
                ? fieldCfg.dependencies.filter((item) => typeof item === 'string' && item !== '')
                : []
            ));
          };
          const resolveDynamicParams = (params, model) => {
            const query = {};

            Object.keys(params || {}).forEach((key) => {
              const value = params[key];
              if (typeof value === 'string' && value.startsWith('@')) {
                const resolved = getByPath(model, value.slice(1));
                if (!isBlank(resolved)) {
                  query[key] = resolved;
                }
                return;
              }

              if (value !== undefined) {
                query[key] = value;
              }
            });

            return query;
          };
          const hasReadyDependencies = (fieldCfg, model) => {
            const dependencies = normalizeDependencies(fieldCfg);
            if (dependencies.length === 0) {
              return true;
            }

            return dependencies.every((path) => !isBlank(getByPath(model, path)));
          };
          const isSameValue = (left, right) => {
            if (left === right) return true;
            if (left === null || left === undefined || right === null || right === undefined) {
              return false;
            }

            return String(left) === String(right);
          };
          const resolveLinkageToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') return '';
            if (path === 'value') return context.value;
            if (path === 'label') return context.option?.label ?? '';
            if (path.startsWith('model.')) return getByPath(context.model, path.slice(6));
            if (path.startsWith('option.')) return getByPath(context.option, path.slice(7));

            return getByPath(context.option, path);
          };
          const resolveLinkageTemplate = (template, context) => {
            if (typeof template === 'function') {
              return template(context);
            }
            if (template === null || template === undefined) {
              return '';
            }
            if (typeof template !== 'string') {
              return template;
            }
            if (/^@[\w.]+$/.test(template)) {
              return resolveLinkageToken(template, context);
            }

            return template.replace(/@[\w.]+/g, (token) => {
              const value = resolveLinkageToken(token, context);
              return value === null || value === undefined ? '' : String(value);
            });
          };
          const buildOptionState = (configs) => {
            const state = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              const fieldCfg = configs[fieldName] || {};
              state[fieldName] = Array.isArray(fieldCfg.initialOptions)
                ? fieldCfg.initialOptions.map((item, index) => normalizeOption(item, fieldCfg, index))
                : [];
            });
            return state;
          };
          const buildFlagState = (configs, initialValue = false) => {
            const state = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              state[fieldName] = initialValue;
            });
            return state;
          };
          const getRemoteOptionsConfig = (scope, fieldName) => {
            const configMap = scope === 'search'
              ? (cfg.searchRemoteOptions || {})
              : (cfg.dialogRemoteOptions || {});

            return configMap[fieldName] || null;
          };
          const getUploadConfigs = (scope) => {
            return scope === 'search'
              ? (cfg.searchUploads || {})
              : (cfg.dialogUploads || {});
          };
          const extractFileName = (url, fallback = 'file') => {
            if (typeof url !== 'string' || url === '') {
              return fallback;
            }

            const clean = url.split('?')[0].split('#')[0];
            const parts = clean.split('/').filter(Boolean);

            return parts[parts.length - 1] || fallback;
          };
          const resolveUploadValue = (payload, fieldCfg, depth = 0) => {
            if (depth > 4 || payload === null || payload === undefined) {
              return null;
            }
            if (typeof payload === 'string') {
              return payload;
            }
            if (!isObject(payload)) {
              return null;
            }

            if (fieldCfg?.responsePath) {
              const pathValue = getByPath(payload, fieldCfg.responsePath);
              if (!isBlank(pathValue)) {
                return pathValue;
              }
            }

            const directKeys = ['url', 'path', 'value', 'src'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'string' && payload[key] !== '') {
                return payload[key];
              }
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const resolved = resolveUploadValue(payload[key], fieldCfg, depth + 1);
                if (!isBlank(resolved)) {
                  return resolved;
                }
              }
            }

            return null;
          };
          const normalizeUploadFile = (item, fieldCfg, index) => {
            if (typeof item === 'string') {
              return {
                uid: 'init-' + index,
                name: extractFileName(item, 'file-' + (index + 1)),
                url: item,
                responseValue: item,
                status: 'success'
              };
            }
            if (!isObject(item)) {
              return null;
            }

            const responseValue = item.responseValue
              ?? resolveUploadValue(item.response, fieldCfg)
              ?? resolveUploadValue(item, fieldCfg);
            const url = item.url || item.value || item.src || responseValue;
            if (isBlank(url)) {
              return null;
            }

            return Object.assign({}, item, {
              uid: item.uid || ('file-' + index),
              name: item.name || extractFileName(String(url), 'file-' + (index + 1)),
              url,
              responseValue: responseValue || url,
              status: item.status || 'success'
            });
          };
          const normalizeUploadFiles = (value, fieldCfg) => {
            const source = Array.isArray(value)
              ? value
              : (isBlank(value) ? [] : [value]);
            const files = source
              .map((item, index) => normalizeUploadFile(item, fieldCfg, index))
              .filter(Boolean);

            return fieldCfg?.multiple ? files : files.slice(0, 1);
          };
          const buildUploadFileState = (configs, model) => {
            const state = {};
            Object.keys(configs || {}).forEach((fieldName) => {
              state[fieldName] = normalizeUploadFiles(getByPath(model, fieldName), configs[fieldName] || {});
            });
            return state;
          };
          const syncUploadModelValue = (model, fieldName, fieldCfg, files) => {
            const normalized = normalizeUploadFiles(files, fieldCfg);
            const values = normalized
              .map((file) => file.responseValue || file.url)
              .filter((value) => !isBlank(value));

            setByPath(model, fieldName, fieldCfg?.multiple ? values : (values[0] ?? ''));

            return normalized;
          };
          const app = Vue.createApp({
            data(){
              return {
                searchModel: clone(cfg.searchDefaults),
                searchInitial: clone(cfg.searchDefaults),
                searchRules: cfg.searchRules || {},
                searchOptions: buildOptionState(cfg.searchRemoteOptions),
                searchOptionLoading: buildFlagState(cfg.searchRemoteOptions),
                searchOptionLoaded: buildFlagState(cfg.searchRemoteOptions),
                searchUploadFiles: buildUploadFileState(cfg.searchUploads, cfg.searchDefaults),
                dialogForm: clone(cfg.dialogDefaults),
                dialogInitial: clone(cfg.dialogDefaults),
                dialogRules: cfg.dialogRules || {},
                dialogOptions: buildOptionState(cfg.dialogRemoteOptions),
                dialogOptionLoading: buildFlagState(cfg.dialogRemoteOptions),
                dialogOptionLoaded: buildFlagState(cfg.dialogRemoteOptions),
                dialogUploadFiles: buildUploadFileState(cfg.dialogUploads, cfg.dialogDefaults),
                dialogVisible: false,
                dialogMode: 'create',
                dialogSubmitting: false,
                tableRows: clone(cfg.initialRows),
                tableAllRows: clone(cfg.initialRows),
                tableTotal: Array.isArray(cfg.initialRows) ? cfg.initialRows.length : 0,
                tablePage: 1,
                tablePageSize: cfg.pagination?.pageSize || 20,
                tableSort: {
                  field: '',
                  order: null
                },
                tableLoading: false
              };
            },
            mounted(){
              this.registerFormDependencies('search');
              this.registerFormDependencies('dialog');
              this.initializeFormOptions('search');
              this.initializeUploadFiles('search');
              if (cfg.list && cfg.list.type === 'remote') {
                this.loadTableData();
                return;
              }
              this.applyClientTableState();
            },
            methods:{
              getFormRef(refName){
                const formRef = this.\$refs[refName];
                return Array.isArray(formRef) ? formRef[0] : formRef;
              },
              validateForm(refName){
                const formRef = this.getFormRef(refName);
                if (!formRef || typeof formRef.validate !== 'function') {
                  return Promise.resolve(true);
                }

                try {
                  const result = formRef.validate();
                  if (result && typeof result.then === 'function') {
                    return result.then(() => true).catch(() => false);
                  }
                } catch (error) {
                  return Promise.resolve(false);
                }

                return Promise.resolve(true);
              },
              clearFormValidate(refName){
                const formRef = this.getFormRef(refName);
                if (formRef && typeof formRef.clearValidate === 'function') {
                  formRef.clearValidate();
                }
              },
              getOptionState(scope){
                return scope === 'search' ? this.searchOptions : this.dialogOptions;
              },
              getOptionLoadingState(scope){
                return scope === 'search' ? this.searchOptionLoading : this.dialogOptionLoading;
              },
              getOptionLoadedState(scope){
                return scope === 'search' ? this.searchOptionLoaded : this.dialogOptionLoaded;
              },
              getFormModel(scope){
                return scope === 'search' ? this.searchModel : this.dialogForm;
              },
              getUploadFieldConfig(scope, fieldName){
                return getUploadConfigs(scope)[fieldName] || {};
              },
              getUploadModel(scope){
                return this.getFormModel(scope);
              },
              getUploadFileState(scope){
                return scope === 'search' ? this.searchUploadFiles : this.dialogUploadFiles;
              },
              getFieldOptions(scope, fieldName){
                const remoteConfig = getRemoteOptionsConfig(scope, fieldName);
                if (remoteConfig) {
                  return this.getOptionState(scope)[fieldName] || [];
                }

                const optionsMap = scope === 'search'
                  ? (cfg.searchSelectOptions || {})
                  : (cfg.dialogSelectOptions || {});

                return (optionsMap[fieldName] || []).map((item, index) => normalizeOption(item, {}, index));
              },
              getLinkageConfig(scope, fieldName){
                const configMap = scope === 'search'
                  ? (cfg.searchLinkages || {})
                  : (cfg.dialogLinkages || {});

                return configMap[fieldName] || null;
              },
              clearLinkageTargets(scope, fieldName){
                const linkCfg = this.getLinkageConfig(scope, fieldName);
                if (!linkCfg?.updates) {
                  return;
                }

                const model = this.getFormModel(scope);
                Object.keys(linkCfg.updates).forEach((targetField) => {
                  const currentValue = getByPath(model, targetField);
                  setByPath(model, targetField, Array.isArray(currentValue) ? [] : '');
                });
              },
              applyFormLinkage(scope, fieldName, value){
                const linkCfg = this.getLinkageConfig(scope, fieldName);
                if (!linkCfg?.updates) {
                  return;
                }

                const model = this.getFormModel(scope);
                const currentValue = value ?? getByPath(model, fieldName);
                if (isBlank(currentValue)) {
                  if (linkCfg.clearOnEmpty !== false) {
                    this.clearLinkageTargets(scope, fieldName);
                  }
                  return;
                }

                const option = this.getFieldOptions(scope, fieldName)
                  .find((item) => isSameValue(item?.value, currentValue));
                if (!option) {
                  return;
                }

                const context = {
                  scope,
                  fieldName,
                  value: currentValue,
                  option,
                  model
                };

                Object.keys(linkCfg.updates).forEach((targetField) => {
                  setByPath(model, targetField, resolveLinkageTemplate(linkCfg.updates[targetField], context));
                });
              },
              nextRemoteRequestToken(scope, fieldName){
                this.__remoteRequestTokens ??= {};
                const key = scope + ':' + fieldName;
                const token = (this.__remoteRequestTokens[key] || 0) + 1;
                this.__remoteRequestTokens[key] = token;

                return token;
              },
              isLatestRemoteRequestToken(scope, fieldName, token){
                const key = scope + ':' + fieldName;

                return (this.__remoteRequestTokens?.[key] || 0) === token;
              },
              setDependencyResetSuspended(scope, suspended = true){
                this.__dependencyResetLocks ??= {};
                this.__dependencyResetLocks[scope] = suspended;
              },
              isDependencyResetSuspended(scope){
                return this.__dependencyResetLocks?.[scope] === true;
              },
              withDependencyResetSuspended(scope, callback){
                this.setDependencyResetSuspended(scope, true);

                try {
                  callback();
                } finally {
                  Vue.nextTick(() => {
                    this.setDependencyResetSuspended(scope, false);
                  });
                }
              },
              resetRemoteFieldState(scope, fieldName, clearValue = false){
                this.nextRemoteRequestToken(scope, fieldName);
                this.getOptionState(scope)[fieldName] = [];
                this.getOptionLoadingState(scope)[fieldName] = false;
                this.getOptionLoadedState(scope)[fieldName] = false;

                if (!clearValue) {
                  return;
                }

                const model = this.getFormModel(scope);
                const currentValue = getByPath(model, fieldName);
                setByPath(model, fieldName, Array.isArray(currentValue) ? [] : '');
              },
              registerFormDependencies(scope){
                const configMap = scope === 'search'
                  ? (cfg.searchRemoteOptions || {})
                  : (cfg.dialogRemoteOptions || {});

                Object.keys(configMap).forEach((fieldName) => {
                  const fieldCfg = configMap[fieldName] || {};
                  const dependencies = normalizeDependencies(fieldCfg);
                  if (dependencies.length === 0) {
                    return;
                  }

                  this.\$watch(
                    () => JSON.stringify(dependencies.map((path) => getByPath(this.getFormModel(scope), path))),
                    () => {
                      const shouldClear = fieldCfg.clearOnChange !== false && !this.isDependencyResetSuspended(scope);
                      this.reloadDependentFieldOptions(scope, fieldName, shouldClear);
                    }
                  );
                });
              },
              reloadDependentFieldOptions(scope, fieldName, clearValue = true){
                this.resetRemoteFieldState(scope, fieldName, clearValue);

                return this.loadFormFieldOptions(scope, fieldName, true);
              },
              initializeFormOptions(scope, force = false){
                const configMap = scope === 'search'
                  ? (cfg.searchRemoteOptions || {})
                  : (cfg.dialogRemoteOptions || {});

                Object.keys(configMap).forEach((fieldName) => {
                  this.loadFormFieldOptions(scope, fieldName, force);
                });
              },
              loadFormFieldOptions(scope, fieldName, force = false){
                const fieldCfg = getRemoteOptionsConfig(scope, fieldName);
                if (!fieldCfg?.url) {
                  return Promise.resolve([]);
                }

                const model = this.getFormModel(scope);
                if (!hasReadyDependencies(fieldCfg, model)) {
                  this.resetRemoteFieldState(scope, fieldName);
                  return Promise.resolve([]);
                }

                const loadingState = this.getOptionLoadingState(scope);
                const loadedState = this.getOptionLoadedState(scope);
                if (loadingState[fieldName]) {
                  return Promise.resolve(this.getOptionState(scope)[fieldName] || []);
                }
                if (!force && loadedState[fieldName]) {
                  return Promise.resolve(this.getOptionState(scope)[fieldName] || []);
                }

                const requestToken = this.nextRemoteRequestToken(scope, fieldName);
                loadingState[fieldName] = true;

                return makeRequest({
                  method: fieldCfg.method || 'get',
                  url: fieldCfg.url,
                  query: Object.assign({}, resolveDynamicParams(fieldCfg.params || {}, model))
                })
                  .then((response) => {
                    if (!this.isLatestRemoteRequestToken(scope, fieldName, requestToken)) {
                      return this.getOptionState(scope)[fieldName] || [];
                    }

                    const payload = ensureSuccess(extractPayload(response), '选项加载失败');
                    const options = pickRows(payload).map((item, index) => normalizeOption(item, fieldCfg, index));
                    this.getOptionState(scope)[fieldName] = options;
                    loadedState[fieldName] = true;

                    return options;
                  })
                  .catch((error) => {
                    if (!this.isLatestRemoteRequestToken(scope, fieldName, requestToken)) {
                      return [];
                    }

                    loadedState[fieldName] = false;
                    const message = error?.message || resolveMessage(error?.response?.data, '选项加载失败');
                    ElementPlus.ElMessage.error(message);
                    return [];
                  })
                  .finally(() => {
                    if (this.isLatestRemoteRequestToken(scope, fieldName, requestToken)) {
                      loadingState[fieldName] = false;
                    }
                  });
              },
              initializeUploadFiles(scope){
                const configs = getUploadConfigs(scope);
                const model = this.getUploadModel(scope);
                const state = buildUploadFileState(configs, model);

                if (scope === 'search') {
                  this.searchUploadFiles = state;
                  return;
                }

                this.dialogUploadFiles = state;
              },
              handleUploadSuccess(scope, fieldName, response, uploadFile, uploadFiles){
                const fieldCfg = this.getUploadFieldConfig(scope, fieldName);

                try {
                  const payload = ensureSuccess(response, '上传失败');
                  const storedValue = resolveUploadValue(payload, fieldCfg);
                  if (isBlank(storedValue)) {
                    throw new Error(resolveMessage(payload, '上传返回数据无效'));
                  }

                  const nextFiles = normalizeUploadFiles(
                    (uploadFiles || []).map((file) => {
                      if (file.uid === uploadFile.uid) {
                        return Object.assign({}, file, {
                          url: typeof storedValue === 'string' ? storedValue : (file.url || ''),
                          responseValue: storedValue
                        });
                      }

                      return file;
                    }),
                    fieldCfg
                  );

                  this.getUploadFileState(scope)[fieldName] = nextFiles;
                  syncUploadModelValue(this.getUploadModel(scope), fieldName, fieldCfg, nextFiles);
                  ElementPlus.ElMessage.success(resolveMessage(payload, '上传成功'));
                } catch (error) {
                  const nextFiles = normalizeUploadFiles(
                    (uploadFiles || []).filter((file) => file.uid !== uploadFile.uid),
                    fieldCfg
                  );
                  this.getUploadFileState(scope)[fieldName] = nextFiles;
                  syncUploadModelValue(this.getUploadModel(scope), fieldName, fieldCfg, nextFiles);
                  ElementPlus.ElMessage.error(error?.message || '上传失败');
                }
              },
              handleUploadRemove(scope, fieldName, uploadFile, uploadFiles){
                const fieldCfg = this.getUploadFieldConfig(scope, fieldName);
                const nextFiles = normalizeUploadFiles(uploadFiles || [], fieldCfg);
                this.getUploadFileState(scope)[fieldName] = nextFiles;
                syncUploadModelValue(this.getUploadModel(scope), fieldName, fieldCfg, nextFiles);
              },
              handleUploadExceed(scope, fieldName, files, uploadFiles){
                const fieldCfg = this.getUploadFieldConfig(scope, fieldName);
                const limit = fieldCfg.limit || 1;
                ElementPlus.ElMessage.error('最多只能上传 ' + limit + ' 个文件');
              },
              handleUploadPreview(uploadFile){
                const url = uploadFile?.url
                  || uploadFile?.responseValue
                  || resolveUploadValue(uploadFile?.response || uploadFile, {});

                if (isBlank(url)) {
                  return;
                }

                window.open(String(url), '_blank');
              },
              submitSearch(){
                this.validateForm('searchFormRef').then((valid) => {
                  if (!valid) return;
                  this.tablePage = 1;
                  this.loadTableData();
                });
              },
              resetSearch(){
                this.withDependencyResetSuspended('search', () => {
                  this.searchModel = clone(this.searchInitial);
                  this.tablePage = 1;
                  this.clearFormValidate('searchFormRef');
                  this.initializeFormOptions('search', true);
                  this.initializeUploadFiles('search');
                });
                this.loadTableData();
              },
              openDialog(dialogKey, row){
                this.withDependencyResetSuspended('dialog', () => {
                  this.dialogMode = row ? 'edit' : 'create';
                  this.dialogForm = Object.assign(clone(this.dialogInitial), row ? clone(row) : {});
                  this.dialogVisible = true;
                  this.initializeFormOptions('dialog', true);
                  this.initializeUploadFiles('dialog');
                });
                Vue.nextTick(() => {
                  this.clearFormValidate('dialogFormRef');
                });
              },
              closeDialog(){
                this.clearFormValidate('dialogFormRef');
                this.dialogVisible = false;
                this.dialogSubmitting = false;
                this.withDependencyResetSuspended('dialog', () => {
                  this.dialogForm = clone(this.dialogInitial);
                  this.initializeUploadFiles('dialog');
                });
              },
              loadTableData(){
                if (!cfg.list || !cfg.list.url) {
                  this.applyClientTableState();
                  return;
                }
                if (cfg.list.type !== 'remote') {
                  this.applyClientTableState();
                  return;
                }
                this.tableLoading = true;
                const request = Object.assign({}, cfg.list, {
                  query: Object.assign(
                    {},
                    cfg.list.query || {},
                    buildSearchQuery(this.searchModel, cfg.searchSchema),
                    cfg.pagination?.enabled === false ? {} : {
                      page: this.tablePage,
                      pageSize: this.tablePageSize
                    },
                    this.tableSort.field ? {
                      order: {
                        field: cfg.sortFieldMap?.[this.tableSort.field] || this.tableSort.field,
                        order: this.tableSort.order
                      }
                    } : {}
                  )
                });
                makeRequest(request)
                  .then((response) => {
                    const payload = ensureSuccess(extractPayload(response), '数据加载失败');
                    this.tableRows = pickRows(payload);
                    this.tableAllRows = clone(this.tableRows);
                    this.tableTotal = pickTotal(payload) ?? this.tableRows.length;
                  })
                  .catch((error) => {
                    const message = error?.message || resolveMessage(error?.response?.data, '数据加载失败');
                    ElementPlus.ElMessage.error(message);
                  })
                  .finally(() => {
                    this.tableLoading = false;
                  });
              },
              applyClientTableState(){
                let rows = clone(cfg.initialRows);
                rows = applyLocalSearch(rows, this.searchModel, cfg.searchSchema);
                if (this.tableSort.field && this.tableSort.order) {
                  rows.sort((left, right) => compareValues(left[this.tableSort.field], right[this.tableSort.field], this.tableSort.order));
                }
                this.tableAllRows = clone(rows);
                this.tableTotal = rows.length;
                if (cfg.pagination?.enabled === false) {
                  this.tableRows = rows;
                  return;
                }
                const start = (this.tablePage - 1) * this.tablePageSize;
                this.tableRows = rows.slice(start, start + this.tablePageSize);
              },
              handlePageChange(page){
                this.tablePage = page;
                this.loadTableData();
              },
              handlePageSizeChange(pageSize){
                this.tablePageSize = pageSize;
                this.tablePage = 1;
                this.loadTableData();
              },
              handleSortChange({ prop, order }){
                this.tableSort = {
                  field: prop || '',
                  order: order || null
                };
                this.loadTableData();
              },
              submitDialog(){
                this.validateForm('dialogFormRef').then((valid) => {
                  if (!valid) return;
                  if (!cfg.saveUrl) {
                    this.closeDialog();
                    return;
                  }

                  this.dialogSubmitting = true;
                  axios.post(cfg.saveUrl, this.dialogForm)
                    .then((response) => {
                      const payload = ensureSuccess(extractPayload(response), '保存失败');
                      ElementPlus.ElMessage.success(resolveMessage(payload, '保存成功'));
                      this.closeDialog();
                      this.loadTableData();
                    })
                    .catch((error) => {
                      const message = error?.message || resolveMessage(error?.response?.data, '保存失败');
                      ElementPlus.ElMessage.error(message);
                    })
                    .finally(() => {
                      this.dialogSubmitting = false;
                    });
                });
              },
              deleteRow(row){
                if (!cfg.deleteUrl || !row) return;
                ElementPlus.ElMessageBox.confirm('确认删除当前记录？', '提示', { type: 'warning' })
                  .then(() => {
                    const payload = (cfg.deleteKey && row[cfg.deleteKey] !== undefined)
                      ? { [cfg.deleteKey]: row[cfg.deleteKey] }
                      : row;
                    return axios.post(cfg.deleteUrl, payload);
                  })
                  .then((response) => {
                    const payload = ensureSuccess(extractPayload(response), '删除失败');
                    if ((cfg.pagination?.enabled !== false) && this.tableRows.length <= 1 && this.tablePage > 1) {
                      this.tablePage -= 1;
                    }
                    ElementPlus.ElMessage.success(resolveMessage(payload, '删除成功'));
                    this.loadTableData();
                  })
                  .catch((error) => {
                    if (error === 'cancel') return;
                    const message = error?.message || resolveMessage(error?.response?.data, '删除失败');
                    ElementPlus.ElMessage.error(message);
                  });
              }
            }
          });
          app.use(ElementPlus, { locale: ElementPlusLocaleZhCn });
          app.mount('#app');
        })($config);
        JS;
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

    private function jsModelAccessor(string $root, string $path): string
    {
        $expression = $root;
        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $segment)) {
                $expression .= '.' . $segment;
                continue;
            }

            $expression .= '[' . $this->jsLiteral($segment) . ']';
        }

        return $expression;
    }

    private function jsReadableAccessor(string $root, string $path): string
    {
        $expression = $root;
        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $segment)) {
                $expression .= '?.' . $segment;
                continue;
            }

            $expression .= '?.[' . $this->jsLiteral($segment) . ']';
        }

        return $expression;
    }

    private function jsBlankCheck(string $expression): string
    {
        return sprintf(
            "(%s === '' || %s === null || %s === undefined || (Array.isArray(%s) && %s.length === 0))",
            $expression,
            $expression,
            $expression,
            $expression,
            $expression
        );
    }

    private function jsTruthyValueCheck(string $expression): string
    {
        return sprintf(
            "([true, 1, '1', 'true', 'yes', 'on'].includes(%s) || ['true', 'yes', 'on', '1'].includes(String(%s).toLowerCase()))",
            $expression,
            $expression
        );
    }

    private function jsFalsyValueCheck(string $expression): string
    {
        return sprintf(
            "([false, 0, '0', 'false', 'no', 'off'].includes(%s) || ['false', 'no', 'off', '0'].includes(String(%s).toLowerCase()))",
            $expression,
            $expression
        );
    }

    private function jsDateFormatExpression(string $valueExpression, string $format): string
    {
        $formatLiteral = $this->jsLiteral($format);

        return <<<JS
(() => {
  const value = {$valueExpression};
  if (value === '' || value === null || value === undefined) return '';

  const raw = String(value).trim();
  const isNumeric = typeof value === 'number' || /^-?\d+(\.\d+)?$/.test(raw);
  const normalizedNumber = isNumeric ? Number(value) : NaN;
  const timestamp = isNumeric
    ? (String(Math.trunc(Math.abs(normalizedNumber))).length <= 10 ? normalizedNumber * 1000 : normalizedNumber)
    : NaN;
  const date = isNumeric
    ? new Date(timestamp)
    : new Date(raw.replace('T', ' ').replace(/-/g, '/'));

  if (Number.isNaN(date.getTime())) return raw;

  const pad = (num) => String(num).padStart(2, '0');
  return {$formatLiteral}
    .replace(/YYYY/g, String(date.getFullYear()))
    .replace(/MM/g, pad(date.getMonth() + 1))
    .replace(/DD/g, pad(date.getDate()))
    .replace(/HH/g, pad(date.getHours()))
    .replace(/mm/g, pad(date.getMinutes()))
    .replace(/ss/g, pad(date.getSeconds()));
})()
JS;
    }

    private function normalizeFieldExpression(?\Stringable $expression, string $modelName): ?string
    {
        if ($expression === null) {
            return null;
        }

        $raw = trim((string)$expression);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/(?<![\w$])model(?![\w$])/', $modelName, $raw);

        return $normalized ?: $raw;
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

    private function jsLiteral(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return match (true) {
            is_int($value), is_float($value) => (string)$value,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => "'" . str_replace(
                ['\\', '\''],
                ['\\\\', '\\\''],
                (string)$value
            ) . "'",
        };
    }

    private function getSortFieldMap(?Table $table): array
    {
        if (!$table) {
            return [];
        }

        $map = [];
        foreach ($table->columns() as $column) {
            if ($column->isSortable()) {
                $map[$column->prop()] = $column->getSortField();
            }
        }

        return $map;
    }
}
