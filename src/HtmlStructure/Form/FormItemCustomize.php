<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCustomizeThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemCustomize
 */
class FormItemCustomize extends AbstractFormItem implements FormItemInterface
{
    public function __construct(protected AbstractHtmlElement|string $element)
    {}

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemCustomizeThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    public function getElement(): AbstractHtmlElement|string
    {
        return $this->element;
    }
}