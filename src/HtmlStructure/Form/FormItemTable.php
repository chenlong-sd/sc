<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTableThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemLine
 */
class FormItemTable extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, FormOrigin, LabelWidth;

    /**
     * @var FormItemInterface[]|AbstractFormItem[]|FormItemAttrGetter[]
     */
    protected array $children = [];

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemTableThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * @param FormItemInterface ...$formItem
     *
     * @return $this
     */
    public function addItems(FormItemInterface ...$formItem): FormItemTable
    {
        $this->children = array_merge($this->children, $formItem);
        return $this;
    }

    protected function getDefault()
    {
        if ($this->default){
            return $this->default;
        }

        $rowData = array_merge(...array_map(function ($v) {
            if ($v->getName()) {
                return [$v->getName() => $v->getDefault()];
            }

            return $v->getDefault();
        }, array_filter($this->children, fn($v) => !$v instanceof FormItemSubmit)));

        return [$rowData];
    }


    public function setFormModel(string $formModel): void
    {
        $this->formModel = $formModel;

        $this->childrenFormSet('setFormModel', "scope.row");
    }
}