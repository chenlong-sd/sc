<?php

namespace Sc\Util\Tool;

/**
 * 随机数
 *
 * Class Random
 */
class Random
{
    const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    const NUMBER = '0123456789';
    const SYMBOL = '!@#$%^&*()_+-=|[]{};:,.<>?/';

    public function __construct(private readonly ?string $prefix = null){}


    public function get(int $min = 0, int $max = 9999): string
    {
        mt_srand();
        return $this->prefix . mt_rand($min, $max);
    }

    public function getStr(int $length, string $chars = null): string
    {
        if ($chars === null) {
            $chars = self::UPPERCASE . self::LOWERCASE . self::NUMBER . self::SYMBOL;
        }

        $max = strlen($chars) - 1;
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            mt_srand();
            $str .= $chars[mt_rand(0, $max)];
        }

        return $this->prefix . $str;
    }

    public function getUpperCase(int $length = 8): string
    {
        return $this->getStr($length, self::UPPERCASE);
    }

    public function getLowerCase(int $length = 8): string
    {
        return $this->getStr($length, self::LOWERCASE);
    }

    public function getNumber(int $length = 8): string
    {
        return $this->getStr($length, self::NUMBER);
    }

    public function getSymbol(int $length = 8): string
    {
        return $this->getStr($length, self::SYMBOL);
    }

    public function getAlphabet(int $length = 8): string
    {
        return $this->getStr($length, self::UPPERCASE . self::LOWERCASE);
    }

    public function getAlphanumeric(int $length = 8): string
    {
        return $this->getStr($length, self::UPPERCASE . self::LOWERCASE . self::NUMBER);
    }
}