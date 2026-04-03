<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Form;

final class FormRenderer
{
    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
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

        if ($form->isInline()) {
            $element->setAttr(':inline', 'true');
            foreach ($form->fields() as $field) {
                $fieldElement = $this->fieldRenderer->render($field, $modelName, true, $options);
                if ($fieldElement->toHtml()) {
                    $element->append($fieldElement);
                }
            }
        } else {
            $row = El::double('el-row')->setAttr(':gutter', 16);
            foreach ($form->fields() as $field) {
                $fieldElement = $this->fieldRenderer->render($field, $modelName, false, $options);
                if ($fieldElement->toHtml()) {
                    $row->append($fieldElement);
                }
            }
            $element->append($row);
        }

        if ($options->isFilterMode()) {
            $element->append($this->renderFilterActions($form, $options));
        }

        return $element;
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
}
