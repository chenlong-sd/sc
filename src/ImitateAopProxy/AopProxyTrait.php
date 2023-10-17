<?php

namespace Sc\Util\ImitateAopProxy;

/**
 * Trait ProxyTrait
 * @method static static aop(bool $useAop = true)
 */
trait AopProxyTrait
{

    /**
     * @return ImitateAopProxy|static
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/9/2
     */
    public function proxy(): mixed
    {
        return new ImitateAopProxy($this);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if ($name === 'aop') {
            if ($arguments && current($arguments) === false) {
                return new static();
            }

            return new ImitateAopProxy(new static());
        }

        throw new \BadMethodCallException();
    }
}
