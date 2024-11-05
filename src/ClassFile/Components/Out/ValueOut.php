<?php

namespace Sc\Util\ClassFile\Components\Out;

use JetBrains\PhpStorm\Language;

/**
 * Class ValueOUt
 */
class ValueOut
{
    private static array $replace = [
        "/\s*'deleted_time'\s*=>\s*'int',\s*'updated_time'\s*=>\s*'datetime',\s*'created_time'\s*=>\s*'datetime'\s*/" => "...self::DEFAULT_CASTS"
    ];

    public static function registerReplace(#[Language('PhpRegExp')] string $rule, string $replacement): void
    {
        self::$replace[$rule] = $replacement;
    }

    public static function out(mixed $value, int $indentation = 4): string
    {
        $res = match (gettype($value)) {
            'string'  => self::getStr($value),
            'array'   => (new ArrayOut($value))->outCode($indentation + 4),
            'integer' => $value,
            'boolean' => $value ? "true" : "false",
            'NULL'    => "null",
            default   => (string)$value,
        };

        foreach (self::$replace as $rule => $target) {
            $res = preg_replace($rule, $target, $res);
            $res = preg_replace("/(?<=\[)$target/", sprintf("\r\n%s$target\r\n%s", str_repeat(' ', $indentation + 4), str_repeat(' ', $indentation)), $res);
        }

        $res = preg_replace_callback('/([\'"])([\w\\\]+)\1/', function ($match){
            return class_exists($match[2]) ? "$match[2]::class" : $match[0];
        }, $res);

        return $res;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function getStr(mixed $value): string
    {
        if (!str_contains($value, "\n")) {
            return str_contains($value, '\'') ? "\"$value\""  :"'$value'";
        }
        return "<<<EOT\r\n$value\r\nEOT";
    }

    /**
     * @param int $indentation
     * @return string
     */
    public static function getIndentation(int $indentation): string
    {
        return $indentation > 0 ? str_pad(" ", $indentation) : '';
    }
}