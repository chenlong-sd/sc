<?php

namespace Sc\Util\HtmlStructure\Html;

/**
 * Class Common
 */
class Common
{
    /**
     * 定义base64zip解码函数
     *
     * @return string
     */
    public static function defJsFnBase64Decode($fnName = "scBase64Decode"): string
    {
        Html::js()->defFunc($fnName, ['base64String'], <<<JS
            const binary = atob(base64String);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return new TextDecoder().decode(bytes);
        JS);

        return $fnName;
    }
}