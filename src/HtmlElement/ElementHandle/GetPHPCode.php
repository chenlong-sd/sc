<?php

namespace Sc\Util\HtmlElement\ElementHandle;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlElement\ElementType\SingleLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;

/**
 * 获取构建代码的php代码
 */
class GetPHPCode
{
    public function __construct(private readonly AbstractHtmlElement $element)
    {
        if ($this->element instanceof FictitiousLabel){
            throw new \Exception('多个同级请使用父标签包裹');
        }
    }

    public function getPHPCode($retraction = 1): string
    {
        return $this->out($this->element, $retraction);
    }

    private function out(AbstractHtmlElement $element, $retraction): string
    {
        if ($element instanceof TextCharacters){
            return "t('{$element->getText()}')";
        }

        $out = "h('{$element->getLabel()}'";

        $childrenIsText = false;
        if ($element instanceof DoubleLabel && count($element->getChildren()) == 1 && $element->getChildren()[0] instanceof TextCharacters){
            $out .= ", '{$element->getChildren()[0]->getText()}'";
            $childrenIsText = true;
        }

        $attrStr = [];
        foreach ($element->getAttrs() as $attr => $value) {
            $attrStr[] = "'{$attr}' => '{$value}'";
        }
        $out .= count($attrStr) > 0 ? ", [" . implode(', ', $attrStr) . "]" : '';

        if ($element instanceof SingleLabel || $childrenIsText){
            return $out . ')';
        }

        $chStr = [];
        foreach ($element->getChildren() as $el) {
            $chStr[] = $this->out($el, $retraction + 1);
        }
        $retractionStr = str_repeat('    ', $retraction);
        $oRetractionStr = str_repeat('    ', $retraction - 1);
        $out .= count($chStr) > 0 ? ")->append(\n$retractionStr" . implode(",\n$retractionStr", $chStr) . "\n$oRetractionStr)" : ')';

        return $out;
    }
}