<?php


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
     * @param string $tag
     * @param string|Stringable|array|null $content
     * @param array $attrs
     * @return mixed|\Sc\Util\HtmlElement\ElementType\DoubleLabel|\Sc\Util\HtmlElement\ElementType\SingleLabel
     */
    function h(string $tag, string|\Stringable|array $content = null, array $attrs = [])
    {
        if (in_array($tag, \Sc\Util\HtmlElement\ElementType\SingleLabel::PREDEFINE_LABEL)){
            $el = \Sc\Util\HtmlElement\El::single($tag);
        }else{
            $el = \Sc\Util\HtmlElement\El::double($tag);
        }
        if ($attrs){
            $el->setAttrs($attrs);
        }
        if ($content && $el instanceof \Sc\Util\HtmlElement\ElementType\DoubleLabel){
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
     * @return \Sc\Util\HtmlElement\ElementType\TextCharacters
     */
    function t(string $text): \Sc\Util\HtmlElement\ElementType\TextCharacters
    {
        return \Sc\Util\HtmlElement\El::text($text);
    }
}