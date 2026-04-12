<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;

trait AppliesRenderableAttributes
{
    /**
     * @param array<string, mixed> $attrs
     */
    protected function applyRenderableAttributes(AbstractHtmlElement $element, array $attrs): AbstractHtmlElement
    {
        foreach ($this->normalizeRenderableAttributes($attrs) as $attr => $value) {
            if (!is_string($attr) || trim($attr) === '' || $value === null) {
                continue;
            }

            if ($attr === 'class') {
                $value = $this->mergeClassAttributeValue($element, $value);
            } elseif ($attr === 'style') {
                $value = $this->mergeStyleAttributeValue($element, $value);
            }

            $element->setAttr($attr, $value);
        }

        return $element;
    }

    private function mergeClassAttributeValue(AbstractHtmlElement $element, string|int $value): string
    {
        $current = trim((string)$element->getAttr('class', ''));
        $value = trim((string)$value);

        if ($current === '') {
            return $value;
        }

        if ($value === '') {
            return $current;
        }

        return trim($current . ' ' . $value);
    }

    private function mergeStyleAttributeValue(AbstractHtmlElement $element, string|int $value): string
    {
        $current = trim((string)$element->getAttr('style', ''));
        $value = trim((string)$value);

        if ($current === '') {
            return $value;
        }

        if ($value === '') {
            return $current;
        }

        return rtrim($current, '; ') . '; ' . ltrim($value, '; ');
    }
}
