<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemGroupThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemLine
 */
class FormItemGroup extends AbstractFormItem implements FormItemInterface
{
    use DefaultValue, FormOrigin;

    /**
     * @var FormItemInterface[]|AbstractFormItem[]|FormItemAttrGetter[]
     */
    protected array $children = [];
    protected ?string $label;
    protected ?string $name;
    /**
     * @var true
     */
    protected bool $plain;

    public function __construct(FormItemInterface|string ...$children)
    {
        if (empty($children)) return;

        if (is_string($children[0])){
            $this->label = $children[0];
            $this->name  = $children[1] ?? null;
        }else{
            $this->children = $children;
        }
    }

    /**
     * 无卡片样式
     *
     * @return $this
     */
    public function plain(): static
    {
        $this->plain = true;
        return $this;
    }

    /**
     * @param FormItemInterface ...$formItem
     *
     * @return $this
     */
    public function addItems(FormItemInterface ...$formItem): static
    {
        $this->children = array_merge($this->children, $formItem);

        return $this;
    }

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemGroupThemeInterface::class, $theme)->render($this);
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
        }, array_filter($this->getChildren(), fn($v) => !$v instanceof FormItemSubmit && !$v instanceof FormItemCustomize)));
    }

    public function getRules(): array
    {
        return array_merge(...array_map(function ($v) {
            if ($v->getName() && $v->getRules()) {
                return [$v->getName() => $v->getRules()];
            }

            return $v->getRules() ?: [];
        }, array_filter($this->getChildren(), fn($v) => !$v instanceof FormItemSubmit && !$v instanceof FormItemCustomize)));
    }


    public function getChildren(): array
    {
        return array_filter($this->children, fn($children) => !$children->getHide());
    }

    public function readonly(): static
    {
        foreach ($this->getChildren() as $child) {
            $child->readonly();
        }

        return $this;
    }
}