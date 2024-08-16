<?php
/**
 * datetime: 2023/4/15 12:05
 **/

namespace Sc\Util\HtmlElement\ElementHandle;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlElement\StopEachException;

trait ElementQuery
{
    /**
     * 循环每一个元素
     *
     * @param callable $callable
     *
     * @return $this
     * @date 2023/4/15
     */
    public function each(callable $callable): static
    {
        // 如果是虚拟元素，则获取其子元素
        $eloop = $this->parent instanceof FictitiousLabel
            ? $this->parent->getChildren()
            : [$this];

        try {
            $stopEachException = new StopEachException();
            while ($element = array_shift($eloop)) {
                $children = [];
                if ($element instanceof DoubleLabel) {
                    $children = $element->getChildren();
                }

                // 调用callable
                call_user_func($callable, $element, $stopEachException);

                $children and array_unshift($eloop, ...$children);
            }
        } catch (StopEachException) {}

        return $this;
    }

    /**
     * 循环子集元素
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function eachChildren(callable $callable): static
    {
        if (!$this instanceof DoubleLabel) {
            return $this;
        }

        foreach ($this->getChildren() as $element) {
            $callable($element);
        }

        return $this;
    }

    /**
     * @param AbstractHtmlElement $element
     * @param array|callable[]    $filters
     *
     * @return bool
     */
    private static function elementFilter(AbstractHtmlElement $element, array|callable $filters): bool
    {
        if (!$filters) {
            return true;
        }
        $filters = is_callable($filters) ? [$filters] : $filters;

        foreach ($filters as $filter) {
            if (!call_user_func($filter, $element)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 查找 element
     *
     * @param string|callable $selector
     *
     * @return false|mixed|null|DoubleLabel
     * @date 2023/5/5
     */
    public function find(#[Language('JQuery-CSS')]string|callable $selector): mixed
    {
        $selectors = is_string($selector) ? $this->selectorAnalysis($selector) : [$selector];

        $queryElement = $this->queryElement($selectors);

        return $queryElement ? current($queryElement) : null;
    }


    /**
     * 弹出元素
     *
     * @param string|callable|null $selector
     *
     * @return false|mixed|DoubleLabel|null
     */
    public function pop(#[Language('JQuery-CSS')]string|callable $selector = null): mixed
    {
        if ($selector) {
            $element = $this->find($selector);

            $this->remove($element);

            return $element;
        }

        $this->remove();

        return $this;
    }

    /**
     * 后面第几个元素
     *
     * @param int $number
     *
     * @return mixed|AbstractHtmlElement|null
     */
    public function next(int $number = 1): mixed
    {
        return $this->brothers($number);
    }

    /**
     * 前面第几个元素
     *
     * @param int $number
     *
     * @return mixed|AbstractHtmlElement|null
     */
    public function pre(int $number = 1): mixed
    {
        return $this->brothers(-$number);
    }

    /**
     * 获取所有匹配项
     *
     * @param string|callable $selector
     *
     * @return array|AbstractHtmlElement[]|DoubleLabel[]
     * @date 2023/5/5
     */
    public function get(#[Language('JQuery-CSS')] string|callable $selector): array
    {
        $selectors = is_string($selector) ? $this->selectorAnalysis($selector) : [$selector];

        return $this->queryElement($selectors, false);
    }

    /**
     * @param array      $selectors
     * @param bool       $isGetOne
     * @param array|null $queryElements
     * @param int        $currentSelectorIndex
     *
     * @return array
     * @date 2023/5/5
     */
    protected function queryElement(array $selectors, bool $isGetOne = true, array $queryElements = null, int $currentSelectorIndex = 0): array
    {
        $queryElements = $queryElements ?: [$this];

        $queryElementResult = [];
        foreach ($queryElements as $element){
            $nextSelectorIndex  = $currentSelectorIndex;

            if (call_user_func($selectors[$currentSelectorIndex], $element)) {

                $nextSelectorIndex = $currentSelectorIndex + 1;
                if (empty($selectors[$nextSelectorIndex])) {
                    $queryElementResult[] = $element;

                    if ($isGetOne) {
                        break;
                    }

                    $nextSelectorIndex = 0;
                }
            }


            if ($element instanceof DoubleLabel && ($children = $element->getChildren())) {
                $queryElementResult = [...$queryElementResult, ...$this->queryElement($selectors, $isGetOne, $children, $nextSelectorIndex)];

                if ($isGetOne && $queryElementResult) {
                    break;
                }
            }
        }

        return $queryElementResult;
    }

    /**
     *
     *
     * @param string $selector
     *
     * @return array
     * @date 2023/5/3
     */
    private function selectorAnalysis(string $selector): array
    {
        $selectorArr = array_filter(explode(' ', $selector));
        $selectorCallableArr = [];

        foreach ($selectorArr as $selector){

            preg_match_all('/[\.#]?[a-zA-Z][\w\-]*|\[[a-zA-Z:@][\w\-]*(=.*?)?]/', $selector, $match);

            $selectorCallableArr[] = fn(AbstractHtmlElement $element) => $this->htmlJQuerySelectorMatch($match[0], $element);
        }

        return $selectorCallableArr;
    }

    /**
     * @param                     $JQuerySelectors
     * @param AbstractHtmlElement $element
     *
     * @return bool
     * @date 2023/5/3
     */
    private function htmlJQuerySelectorMatch($JQuerySelectors, AbstractHtmlElement $element): bool
    {
        if ($element instanceof TextCharacters){
            return false;
        }

        foreach ($JQuerySelectors as $selector) {
            $result = match (substr($selector, 0, 1)) {
                '#'     => $element->getId() === substr($selector, 1),
                '.'     => $element->hasClass(substr($selector, 1)),
                '['     => substr($selector, 1, -1),
                default => $element->getLabel() === $selector
            };

            if (is_bool($result)) {
                if ($result === false) return false;

                continue;
            }

            if (str_contains($result, '=')) {
                list($attr, $value) = explode('=', $result);
                if ($element->getAttr($attr) != $value) {
                    return false;
                }
            } else {
                if (!$element->hasAttr($result)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param int $number
     *
     * @return mixed|AbstractHtmlElement|null
     */
    private function brothers(int $number): mixed
    {
        $currentLayerEl = $this->getParent()?->getChildren() ?: [];
        $thisIndex      = array_search($this, $currentLayerEl);

        return $currentLayerEl[$thisIndex + $number] ?? null;
    }
}