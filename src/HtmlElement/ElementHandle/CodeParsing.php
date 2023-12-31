<?php
/**
 * datetime: 2023/4/14 1:24
 **/

namespace Sc\Util\HtmlElement\ElementHandle;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlElement\ElementType\SingleLabel;

/**
 * 代码解析
 *
 * Class CodeParsing
 *
 * @package Sc\Util\HtmlElement
 * @date    2023/4/14
 */
class CodeParsing
{
    /**
     * 解析
     *
     * @param string $code
     *
     * @return AbstractHtmlElement
     * @date 2023/4/14
     */
    public static function parsing(string &$code): AbstractHtmlElement
    {
        $base = new FictitiousLabel();

        while ($code) {
            // 文本处理
            if (!str_starts_with($code, '<')) {
                $index   = strpos($code, '<') ?: null;
                if (trim($text = substr($code, 0, $index))) {
                    $base->append(El::text($text));
                }
                $code    = $index === null ? "" : substr($code, $index);
                continue;
            }

            // 结束标签处理
            if (preg_match('/^<\/[\w\-]+>/', $code)) {
                $code = preg_replace('/^<\/[\w\-]+>/', '', $code);
                break;
            }

            // 开始标签解析
            // 解析失败，当作文本处理
            if (!$match = self::tagParsing($code)){
                $base->append(El::text($code));
                break;
            }

            $code  = substr($code, strlen($match[0]));
            $attrs = self::attrParsing($match['attr']);

            // 单标签
            if (!empty($match['s']) || SingleLabel::isSignLabel($match['tag'])) {
                $base->append(El::single($match['tag'])->setAttrs($attrs));
                continue;
            }

            // 双标签
            $elements = El::double($match['tag'])->setAttrs($attrs);

            $elements->append(self::parsing($code));

            $base->append($elements);
        }

        return count($base->getChildren()) === 1 ? $base->getChildren()[0] : $base;
    }


    private static function tagParsing(string $code): array
    {
        preg_match('/^<(?<tag>[a-zA-Z][\w\-]*)(?<attr>(\s+([:@#a-zA-Z][:@#\.\w\-]*)(=(?<q>[\"\']).*?[^\\\\]?\k<q>)?)*\s*)(?<s>\/)?>/s', $code, $match);

        return $match;
    }

    /**
     * @param string $attrString
     *
     * @return array
     */
    private static function attrParsing(string $attrString): array
    {
        preg_match_all('/(?<name>[:@#a-zA-Z][:@#\.\w\-]*)(=(?<q>[\"\'])(?<value>.*?[^\\\\]?)\k<q>)?/', $attrString, $match);

        if ($match) {
            return array_combine($match['name'], $match['value']);
        }

        return [];
    }
}