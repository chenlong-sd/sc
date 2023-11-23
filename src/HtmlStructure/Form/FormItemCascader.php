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
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCascaderThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemCascader
 */
class FormItemCascader extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Placeholder, Options, LabelWidth, FormOrigin, Events, Attrs;

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemCascaderThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }
}