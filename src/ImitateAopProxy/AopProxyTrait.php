<?php

namespace Sc\Util\ImitateAopProxy;

/**
 * 仿造AOP的代理类Trait
 * 提示：引入此trait的类不要有存储实时数据的属性，因为会缓存代理类，重复调用的时候会直接到缓存获取类
 *
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
        return ImitateAopProxy::getProxy($this);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if ($name === 'aop') {
            if ($arguments && current($arguments) === false) {
                return new static();
            }

            return ImitateAopProxy::getProxy(static::class);
        }

        throw new \BadMethodCallException();
    }
}
