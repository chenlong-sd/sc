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
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemSelect
 */
class FormItemSelect extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs;

    /**
     * @var true
     */
    protected bool $multiple = false;

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemSelectThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * @return $this
     */
    public function multiple(): static
    {
        $this->multiple = true;

        return $this;
    }
}