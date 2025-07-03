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
use Sc\Util\HtmlStructure\Form\ItemAttrs\Options;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Slots;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTagThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemText
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
class FormItemTag extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs, Validate, Slots;


    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemTagThemeInterface::class, $theme)->render($this);
    }

    public function max(int $max): static
    {
        return $this->setVAttrs(':max', $max);
    }

    public function readonly(): static
    {
        return $this->setVAttrs(':disabled', 'true');
    }

    public function draggable(): static
    {
        return $this->setVAttrs(':draggable', 'true');
    }
}