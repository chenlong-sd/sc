<?php
/**
 * datetime: 2023/6/3 2:47
 **/

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextareaThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemText
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
class FormItemTextarea extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Placeholder, LabelWidth, FormOrigin, Events, Attrs, Validate;

    protected array $autocomplete;

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemTextareaThemeInterface::class, $theme)->render($this);
    }

    /**
     * @param array|string                    $data
     * @param AbstractHtmlElement|string|null $template
     *
     * @return $this
     */
    public function setAutoComplete(array|string $data, AbstractHtmlElement|string $template = null): static
    {
        $this->autocomplete = compact('data', 'template');

        return $this;
    }
}