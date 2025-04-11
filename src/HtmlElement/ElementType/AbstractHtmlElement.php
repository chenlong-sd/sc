<?php
/**
 * datetime: 2023/4/13 2:59
 **/

namespace Sc\Util\HtmlElement\ElementType;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementHandle\ElementQuery;

/**
 * 抽象元素
 * Class AbstractElement
 *
 * @package Sc\Util\Element
 * @date    2023/4/13
 * @mixin DoubleLabel
 */
abstract class AbstractHtmlElement
{
    use ElementQuery;

    private DoubleLabel|null $parent = null;
    private int $retraction = 4;

    /**
     * 渲染自身 htmlCode
     *
     * @return string
     * @date 2023/4/15
     */
    abstract public function toHtml(): string;

    /**
     * 向元素前面添加元素
     *
     * @param AbstractHtmlElement|string $element
     *
     * @return AbstractHtmlElement
     * @date 2023/4/13
     */
    public function before(AbstractHtmlElement|string $element): static
    {
        if (!$this->parent) {
            $this->parent = new FictitiousLabel();
            $this->parent->append($this);
        }

        $this->parent->insert($element, $this->parent->searchChildren($this) ?: 0);

        return $this;
    }

    /**
     * 向后添加元素
     *
     * @param AbstractHtmlElement|string $element
     *
     * @return $this
     * @date 2023/4/15
     */
    public function after(AbstractHtmlElement|string $element): static
    {
        if (!$this->parent) {
            $this->parent = new FictitiousLabel();
            $this->parent->append($this);
        }

        $this->parent->insert($element, ($this->parent->searchChildren($this) ?: 0) + 1);

        return $this;
    }

    /**
     * @param string|null $selector
     *
     * @return DoubleLabel|null
     * @date 2023/4/15
     */
    public function getParent(#[Language('JQuery-CSS')] string $selector = null): ?DoubleLabel
    {
        if (!$selector || !$this->parent || $this->parent->find($selector)) {
            return $this->parent;
        }

        return $this->parent->getParent($selector);
    }

    /**
     * 获取缩进
     *
     * @return string
     */
    public function getCurrentRetraction(): string
    {
        if ($this->getParent() && !($this->getParent() instanceof FictitiousLabel)) {
            $parentRetraction = $this->getParent()->getCurrentRetraction();
            if ($this->retraction > 0) {
                return $parentRetraction . str_repeat(' ', $this->retraction);
            }

            if ($this->retraction < 0) {
                return substr($parentRetraction, 0, $this->retraction);
            }

            return $parentRetraction;
        }

        return '';
    }


    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * @param DoubleLabel|null $parent
     *
     * @return AbstractHtmlElement
     */
    public function setParent(?DoubleLabel $parent): AbstractHtmlElement
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * 复制自己
     *
     * @return AbstractHtmlElement|$this
     */
    public function copy(): AbstractHtmlElement|static
    {
        return clone $this;
    }

    /**
     * @param AbstractHtmlElement|null $element
     *
     * @return void
     */
    public function remove(?AbstractHtmlElement $element = null): void
    {
        if ($element) {
            $childrenKey = $this->searchChildren($element);
            if ($childrenKey !== false) {
                unset($this->childrenNodes[$childrenKey]);
            }
            return;
        }

        $this->parent->remove($this);
    }

    /**
     * 设置输出时的缩进数量
     *
     * @param int $space
     *
     * @return $this
     */
    public function setRetraction(int $space = 4): static
    {
        $this->retraction = $space;

        return $this;
    }

    public function getRetraction(): int
    {
        return $this->retraction;
    }
}