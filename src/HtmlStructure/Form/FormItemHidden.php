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
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemHiddenThemeInterface;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemText
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
class FormItemHidden extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue,FormOrigin, Attrs;

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemHiddenThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

}