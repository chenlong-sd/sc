<?php
/**
 * datetime: 2023/4/13 0:20
 **/

namespace Sc\Util\HtmlElement\ElementType;

/**
 * 纯文本字符
 *
 * Class PureText
 *
 * @package Sc\Util\Element
 * @date    2023/4/13
 */
class TextCharacters extends AbstractHtmlElement
{
    public function __construct(
        protected ?string $text
    ) {
    }

    public function toHtml(): string
    {
        if (!str_contains($this->text,"\n")) {
            return trim($this->text);
        }

        $retraction = "\r\n" . $this->getCurrentRetraction();
        $content = preg_replace('/[\r\n]+(\s*)/', "\r\n$1" . $this->getCurrentRetraction(), trim($this->text),);
        $this->setRetraction($this->getRetraction() - 4);
        return $retraction . $content . "\r\n" . $this->getCurrentRetraction();
    }

    /**
     * @param string $text
     *
     * @return TextCharacters
     */
    public function setText(string $text): TextCharacters
    {
        $this->text = $text;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }
}