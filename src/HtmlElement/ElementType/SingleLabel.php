<?php
/**
 * datetime: 2023/4/13 0:18
 **/

namespace Sc\Util\HtmlElement\ElementType;

use Sc\Util\HtmlElement\ElementHandle\ElementQuery;
use Sc\Util\HtmlElement\ElementHandle\LabelAttr;

/**
 * 单标签
 *
 * Class SingleLabel
 *
 * @package Sc\Util\Element
 * @date    2023/4/13
 */
class SingleLabel extends AbstractHtmlElement
{
    const PREDEFINE_LABEL = [
        'img', 'br', 'meta', 'input', 'link', 'source', 'track', 'hr'
    ];

    use LabelAttr;

    public function toHtml(): string
    {
        return sprintf("%s<%s%s />", "\r\n" . $this->getCurrentRetraction(), $this->label, $this->attrToString());
    }


    /**
     * 默认单标签判断
     *
     * @param string $label
     *
     * @return bool
     */
    public static function isSignLabel(string $label): bool
    {
        return in_array($label, self::PREDEFINE_LABEL);
    }
}