<?php


use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\SingleLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;

if (! function_exists('kv_to_form_options')) {

    /**
     * 将 options[ k => v ] 转为 表单选择适配数据
     *
     * @param array $options
     * @param bool  $keyIsInt
     *
     * @return array
     */
    function kv_to_form_options(array $options, bool $keyIsInt = false): array
    {
        $result = [];
        foreach ($options as $value => $label) {
            $result[] = [
                'value' => $keyIsInt ? (int)$value : $value,
                'label' => $label
            ];
        }

        return $result;
    }
}

if (! function_exists('h')){
    /**
     * 创建标签
     *
     * @param string|array $tag
     * @param string|Stringable|array|null $content
     * @param array $attrs
     * @return mixed|DoubleLabel|SingleLabel
     */
    function h(string|array|AbstractHtmlElement $tag = '', string|\Stringable|array $content = null, array $attrs = [])
    {
        if (is_array($tag)) return El::fictitious()->append(...$tag);

        if ($tag == ''){
            $el = El::fictitious();
        }else if (in_array($tag, SingleLabel::PREDEFINE_LABEL)){
            $el = El::single($tag);
        }else if ($tag instanceof AbstractHtmlElement){
            $el = $tag;
        }else if(preg_match('/^[\w-]+$/', $tag)){
            $el = El::double($tag);
        }else{
            $el = El::fromCode($tag);
        }

        if ($attrs){
            $el->setAttrs($attrs);
        }

        if ($content && ($el instanceof DoubleLabel || $el instanceof SingleLabel)){
            is_array($content) ? $el->setAttrs($content) : $el->append($content);
        }

        return $el;
    }
}

if (! function_exists('t')){
    /**
     * 纯文本
     *
     * @param string $text
     * @return TextCharacters
     */
    function t(string $text): TextCharacters
    {
        return El::text($text);
    }
}