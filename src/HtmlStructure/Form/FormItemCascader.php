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
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCascaderThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemCascader
 */
class FormItemCascader extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Placeholder, Options, LabelWidth, FormOrigin, Events, Attrs,Validate;

    /**
     * @var true
     */
    protected bool $closeAfterSelection = false;
    /**
     * @var true
     */
    private bool $isPanel = false;

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemCascaderThemeInterface::class, $theme)->render($this);
    }

    /**
     * 转为面板模式
     *
     * @return $this
     */
    public function toPanel(): static
    {
        $this->isPanel = true;

        return $this;
    }

    /**
     * 选择后即关闭
     *
     * @return $this
     */
    public function closeAfterSelection(): static
    {
        $this->closeAfterSelection = true;

        return $this;
    }

    public function isPanel(): bool
    {
        return $this->isPanel;
    }
}