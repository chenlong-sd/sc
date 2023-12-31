<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemInLineThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemLine
 */
class FormItemInLine extends AbstractFormItem implements FormItemInterface
{
    use DefaultValue, FormOrigin;

    /**
     * @var FormItemInterface[]|AbstractFormItem[]|FormItemAttrGetter[]
     */
    protected array $children;

    public function __construct(FormItemInterface ...$children)
    {
        $this->children = $children;
    }

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemInLineThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * @return array
     */
    public function getDefault(): array
    {
        return array_merge(...array_map(function ($v) {
            if ($v->getName()) {
                return [$v->getName() => $v->getDefault()];
            }

            return $v->getDefault();
        }, array_filter($this->children, fn($v) => !$v instanceof FormItemSubmit)));
    }
}