<?php

namespace Sc\Util\HtmlStructure\Html\Js;

/**
 * Class JsLog
 */
class JsLog
{
    public static function print($data): string
    {
        if (!is_string($data)) {
            return JsCode::raw("console.log( $data );");
        }

        if (str_starts_with($data, '@')) {
            return self::printVar(substr($data, 1));
        }

        return JsCode::raw("console.log( '$data' );");
    }

    public static function printVar(string $varName): string
    {
        return JsCode::raw("console.log( $varName );");
    }
}