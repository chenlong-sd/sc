<?php

namespace Sc\Util\ImitateAopProxy;

use Sc\Util\ImitateAopProxy\Interfaces\ImitateAspectAttrInterface;

/**
 * 仿造AOP的代理类
 * 一、可调用代理类的方法
 * 二、可调用代理类的方法的切面(实现{@see ImitateAspectAttrInterface}接口)注解
 * 调用顺序：
 * 1.调用aop类的__call()
 * 2.调用method的第一个切面handle方法，切面通过注解类的 getImitateAspect 方法获取
 * 3.调用method的第二个切面handle方法，....有多少，一次调用
 * 4.代理类的实际调用method通过切面的handle方法参数传入，为一个匿名函数，自定义调用位置
 * 5.返回值到第二个切面的handle方法，如有更多，则依次返回，
 * 6.返回值到第一个切面的handle方法，
 * 7.返回值到调用位置
 * A > x > y > [C] > y > x > A
 * 三、可调用代理类的公共属性
 * 四、可设置代理类的公共属性
 *
 * Class ImitateAopProxy
 */
final class ImitateAopProxy
{
    private static array $proxyContainers = [];

    private object $proxyClass;

    private array $aspectMapping = [];

    /**
     * @param object|string $class
     *
     */
    private function __construct(object|string $class)
    {
        $this->proxyClass = $class;

        $this->genMapping($this->proxyClass);
    }

    public static function getProxy(object|string $class)
    {
        $key = is_string($class) ? $class : get_class($class);

        if (empty(self::$proxyContainers[$key])) {
            $class = is_object($class) ? $class : new $class();
            self::$proxyContainers[$key] = new self($class);
        }

        return self::$proxyContainers[$key];
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->call($name, $arguments);
    }

    /**
     * @param object $class
     *
     * @return void
     */
    private function genMapping(object $class): void
    {
        $reflectionClass = new \ReflectionClass($class);

        foreach ($reflectionClass->getMethods() as $method) {
            $reflectionAttributes = $method->getAttributes();
            $this->aspectMapping[$method->getName()] = $this->aspectMapping[$method->getName()] ?? [];

            foreach ($reflectionAttributes as $attribute) {
                $aspect = $attribute->newInstance();
                if ($aspect instanceof ImitateAspects) {
                    $this->aspectMapping[$method->getName()] = array_merge($this->aspectMapping[$method->getName()], $aspect->getImitateAspects());
                } elseif ($aspect instanceof ImitateAspectAttrInterface) {
                    $this->aspectMapping[$method->getName()][] = $aspect->getImitateAspect($method);
                }
            }
        }
    }

    /**
     * 调用
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    private function call(string $name, array $arguments): mixed
    {
        $aspectClass = $this->aspectMapping[$name] ?? [];

        if (!$aspectClass) {
            return $this->proxyClass->{$name}(...$arguments);
        }

        $originHandle = fn($arg) => $this->proxyClass->{$name}(...$arg);

        $aspectClass = array_reverse($aspectClass);
        $call = array_reduce($aspectClass, fn($upCallable, $aspect) => fn($arg) => $aspect->handle($upCallable ?: $originHandle, $arg));

        return $call($arguments);
    }
}
