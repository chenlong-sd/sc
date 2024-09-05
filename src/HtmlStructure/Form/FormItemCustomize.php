<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCustomizeThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemCustomize
 */
class FormItemCustomize extends AbstractFormItem implements FormItemInterface
{
    use Attrs;

    protected ?string $label = null;

    public function __construct(protected AbstractHtmlElement|string $element)
    {}

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemCustomizeThemeInterface::class, $theme)->render($this);
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getElement(): AbstractHtmlElement|string
    {
        return $this->element;
    }
}