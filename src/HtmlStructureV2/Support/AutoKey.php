<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class AutoKey
{
    public static function make(string $prefix): string
    {
        return $prefix . self::suffix();
    }

    private static function suffix(): string
    {
        try {
            return \bin2hex(\random_bytes(8));
        } catch (\Throwable) {
            return \str_replace('.', '', \uniqid('', true));
        }
    }
}
