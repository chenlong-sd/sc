<?php

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemGroupThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemLine
 * @property bool $isArrayValue
 * @property callable $callback
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
    protected mixed $initDefault = null;

    protected bool $isArrayValue = false;

    protected  $callback = null;

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


    public function default(mixed $default): static
    {
        $this->getDefault();

        $this->default = $default;

        $this->childrenDefault();

        return $this;
    }


    /**
     * 设置group的阴影
     *
     * @param string $shadow
     * @return $this
     */
    public function shadow(#[ExpectedValues(['always', 'hover', 'never'])]string $shadow = ""): static
    {
        $this->shadow = $shadow;

        return $this;
    }
    
    
    /**
     * @param mixed $default
     * @return void
     */
    protected function setInitDefault(mixed $default): void
    {
        if ($this->initDefault === null) {
            $this->initDefault = $default;
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
     * @throws \Exception
     */
    public function addItems(FormItemInterface ...$formItem): static
    {
        $this->children = array_merge($this->children, $formItem);

        return $this;
    }

    public function render(string $theme = null): AbstractHtmlElement
    {
        if ($this->callback) {
            $this->children = array_map(function ($v) {
                $res = ($this->callback)($v);
                return $res ?: $v;
            }, $this->children);
        }

        if ($this->isArrayValue) {
            $table = array_filter($this->children, fn($v) => $v instanceof FormItemTable);
            if ($table) {
                throw new \Exception('group类型值为数组时，不能嵌套table');
            }
        }

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

        $this->setInitDefault($childrenDefault);

        return $this->isArrayValue ? [] : $childrenDefault;
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
                ? "{$this->name}_item"
                : "$formModel.$this->name";
        }

        // 由于一般调用此函数时元素已经定好，所以可以直接调用这个
        $this->childrenFormSet('setFormModel', $formModel);

        return $this;
    }

    /**
     * 设置为数组，group必须设置name
     *
     * @param string|\Stringable $addText 新增按钮文字或 AbstractHtmlElement, 不是存文字时，将直接作为新增的元素
     * @return $this
     * @throws \Exception
     */
    public function arrayValue(string|\Stringable $addText = "新增一项"): static
    {
        if (!$this->name) {
            throw new \Exception('group类型值为数组时，必须设置name');
        }
        $this->isArrayValue = true;
        $this->arrayAddText = $addText;

        return $this;
    }

    public function readonly(string $when = ''): static
    {
        foreach ($this->getChildren() as $child) {
            $child->readonly($when);
        }

        return $this;
    }


    public function each(callable $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function setLabel(?string $label): FormItemGroup
    {
        $this->label = $label;
        return $this;
    }
}