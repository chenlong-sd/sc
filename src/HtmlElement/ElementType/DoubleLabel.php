<?php
/**
 * datetime: 2023/4/13 0:19
 **/

namespace Sc\Util\HtmlElement\ElementType;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementHandle\LabelAttr;

/**
 * 双标签
 *
 * Class DoubleLabel
 *
 * @package Sc\Util\Element
 * @date    2023/4/13
 */
class DoubleLabel extends AbstractHtmlElement
{
    use LabelAttr;

    /**
     * @var array|AbstractHtmlElement[]
     */
    protected array $childrenNodes = [];

    /**
     * @param array $elements
     *
     * @return array|AbstractHtmlElement[]
     * @date 2023/4/16
     */
    private function getElements(array $elements): array
    {
        $htmlElements = [];

        foreach ($elements as $element) {
            if (!$element instanceof AbstractHtmlElement) {
                $element = El::fromCode($element);
            }

            if ($element instanceof FictitiousLabel) {
                $htmlElements = [...$htmlElements, ...array_map(fn($el) => $el->setParent($this), $element->getChildren())];
                continue;
            }
            $element->setParent($this);
            $htmlElements[] = $element;
        }

        return $htmlElements;
    }

    public function toHtml(): string
    {
        if ($this->getChildren()) {
            // 子项为纯文本时
            if (count($this->getChildren()) === 1 && $this->getChildren()[0] instanceof TextCharacters) {
                return sprintf('%s%s<%s%s>%s</%3$s>',
                    "\r\n",
                    $this->getCurrentRetraction(),
                    $this->label,
                    $this->attrToString(),
                    $this->getChildren()[0]->toHtml()
                );
            }

            return sprintf('%s%s<%s%s>%s%1$s%2$s</%3$s>',
                "\r\n",
                $this->getCurrentRetraction(),
                $this->label,
                $this->attrToString(),
                implode($this->getChildren()),
            );
        }

        // 无子项时
        return sprintf('%s%s<%s%s></%3$s>',
            "\r\n",
            $this->getCurrentRetraction(),
            $this->label,
            $this->attrToString(),
        );
    }

    /**
     * 获取标签内容
     *
     * @return string
     */
    public function getContent(): string
    {
        return trim(implode($this->getChildren()));
    }

    /**
     * 向后追加
     *
     * @param AbstractHtmlElement|string|null ...$elements
     *
     * @return $this
     * @date 2023/5/6
     */
    public function append(AbstractHtmlElement|string|null ...$elements): static
    {
        $elements = array_filter($elements, fn($v) => $v !== null && $v !== '');
        if ($elements) {
            array_push($this->childrenNodes, ...$this->getElements($elements));
        }

        return $this;
    }

    /**
     * @param string $text
     *
     * @return DoubleLabel|$this
     */
    public function text(?string $text): DoubleLabel|static
    {
        $this->append(new TextCharacters($text));

        return $this;
    }

    /**
     * 向前追加
     *
     * @param AbstractHtmlElement|string ...$elements
     *
     * @return $this
     * @date 2023/5/6
     */
    public function prepend(AbstractHtmlElement|string ...$elements): static
    {
        array_unshift($this->childrenNodes, ...$this->getElements($elements));

        return $this;
    }

    /**
     * 插入
     *
     * @param AbstractHtmlElement|string $elements
     * @param int                        $index
     *
     * @return DoubleLabel
     * @date 2023/5/6
     */
    public function insert(AbstractHtmlElement|string $elements, int $index): static
    {
        $elements = $this->getElements([$elements]);

        array_splice($this->childrenNodes, $index, 0, $elements);

        return $this;
    }

    /**
     * 获取子项
     *
     * @return array|AbstractHtmlElement[]
     * @date 2023/5/6
     */
    public function getChildren(): array
    {
        return $this->childrenNodes;
    }

    /**
     * @param int $index
     *
     * @return mixed|AbstractHtmlElement|null
     */
    public function getChildrenByIndex(int $index): mixed
    {
        return $this->childrenNodes[$index] ?? null;
    }

    /**
     * 搜索子项返回索引位置
     *
     * @param AbstractHtmlElement $element
     *
     * @return int|false
     * @date 2023/5/6
     */
    public function searchChildren(AbstractHtmlElement $element): int|false
    {
        return array_search($element, $this->childrenNodes, true);
    }

    /**
     * @param AbstractHtmlElement|string|array $elements
     *
     * @return DoubleLabel|$this
     */
    public function setChildren(AbstractHtmlElement|string|array $elements): static
    {
        $this->childrenNodes = is_array($elements) ? $elements : [El::get($elements)];

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->getChildren();
    }
}