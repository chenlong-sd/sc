<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Options;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSwitchThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemSelect
 */
class FormItemSwitch extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue,  LabelWidth, FormOrigin, Events, Attrs;

    use Options {
        Options::getOptions as protected traitGetOptions;
    }

    protected mixed $openValue = null;

    protected function getOptions(): array
    {
        $options = $this->traitGetOptions();

        if ($this->openValue !== null && $options[0]['value'] != $this->openValue) {
            $options = array_reverse($options);
        }

        return $options;
    }


    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemSwitchThemeInterface::class, $theme)->render($this);
    }

    /**
     * @param mixed $openValue
     *
     * @return FormItemSwitch
     */
    public function setOpenValue(mixed $openValue): FormItemSwitch
    {
        $this->openValue = $openValue;

        return $this;
    }
}