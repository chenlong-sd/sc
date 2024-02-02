<?php

namespace Sc\Util\Tool;

/**
 * 随机数
 *
 * Class Random
 */
class Random
{
    public function __construct(private readonly ?string $prefix = null)
    {
        mt_srand();
    }


    public function get(int $min = 0, int $max = 9999): string
    {
        return $this->prefix . mt_rand($min, $max);
    }
}