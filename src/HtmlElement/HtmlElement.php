<?php
/**
 * datetime: 2023/4/13 0:09
 **/

namespace Sc\Util\HtmlElement;
use Sc\Util\HtmlElement\ElementHandle\CodeParsing;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlElement\ElementType\SingleLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;

/**
 * Html 元素对象
 *
 * Class Element
 *
 * @package Sc\Util\Tool
 * @date    2023/4/13
 */
class HtmlElement
{
    public static function double(string $tagName): DoubleLabel
    {
        return new DoubleLabel($tagName);
    }

    public static function single(string $tagName): SingleLabel
    {
        return new SingleLabel($tagName);
    }

    public static function text(string $text): TextCharacters
    {
        return new TextCharacters($text);
    }

    public static function fictitious(): FictitiousLabel
    {
        return new FictitiousLabel();
    }

    public static function getAttrFromStr(string $attrStr): array
    {
        return CodeParsing::attrParsing($attrStr);
    }

    public static function fromCode(string $code): AbstractHtmlElement
    {
        // 去除 html5 标头
        $code = preg_replace('/^\s*<!DOCTYPE html>/', '', $code);

        // 去除注释
        $code = preg_replace('/<!--.*?-->/', '', $code);

        return CodeParsing::parsing($code);
    }

    public static function get(AbstractHtmlElement|string $element): AbstractHtmlElement
    {
        return is_string($element) ? El::fromCode($element) : $element;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return AbstractHtmlElement
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));

        $el = self::double($name);

        if ($arguments) {
            $el->append(...$arguments);
        }

        return $el;
    }
}