<?php

namespace Sc\Util\ImitateAopProxy;

/**
 * 伪造切片
 *
 * Class Aspect
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ImitateAspects
{
    private array $class;

    /**
     * @param string|array $class 切片类
     */
    public function __construct(string|array $class)
    {
        if (is_string($class)) {
            $class = [$class];
        }

        $this->class = $class;
    }

    /**
     * @return array
     */
    public function getImitateAspects(): array
    {
        return array_map(fn($class) => new $class(), $this->class);
    }
}
