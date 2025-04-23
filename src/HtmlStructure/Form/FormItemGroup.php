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
    protected ?string $label = null;
    protected ?string $name = null;
    /**
     * @var true
     */
    protected bool $plain;
    /**
     * @var true
     */
    protected bool $isArrayValue = false;

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
        if ($this->default) {
            return $this->default;
        }

        $childrenDefault = array_merge(...array_map(function ($v) {
            if ($v->getName()) {
                return [$v->getName() => $v->getDefault()];
            }

            return $v->getDefault();
        }, array_filter($this->getChildren(), fn($v) => !$v instanceof FormItemSubmit && !$v instanceof FormItemCustomize)));

        return $this->isArrayValue ? $childrenDefault : [$childrenDefault];
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

    public function setFormModel(string $formModel): static
    {
        $this->formModel = $formModel;
        if ($this->name) {
            $formModel = $this->isArrayValue
                ? $this->name . '_item'
                : $formModel . '.' . $this->name;
        }

        // 由于一般调用此函数时元素已经定好，所以可以直接调用这个
        $this->childrenFormSet('setFormModel', $formModel);

        return $this;
    }

    /**
     * 设置为数组
     *
     * @return $this
     * @throws \Exception
     */
    public function arrayValue(): static
    {
        if (!$this->name) {
            throw new \Exception('数组值必须设置name');
        }
        $this->isArrayValue = true;

        return $this;
    }

    public function readonly(): static
    {
        foreach ($this->getChildren() as $child) {
            $child->readonly();
        }

        return $this;
    }
}