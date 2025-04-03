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
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCheckboxThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemSelect
 */
class FormItemCheckbox extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, LabelWidth, FormOrigin, Events, Attrs, Validate;

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemCheckboxThemeInterface::class, $theme)->render($this);
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default ?: [];
    }

//    public function readonly(): static
//    {
//        return $this->setVAttrs(':disabled', 'true');
//    }
}