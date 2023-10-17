<?php

namespace Sc\Util\Tool;

/**
 * 类的代理，用于获取类的私有属性，调用类的私有方法
 *
 * Class ClassProxy
 */
class ClassProxy
{
    private \Closure|null|false $setter;
    private \Closure|null|false $getter;
    private \Closure|null|false $methodCall;
    private array $overwriteMethod = [];

    public function __construct(protected object $class)
    {
        $this->bindPropertyGetter();
        $this->bindPropertySetter();
        $this->bindMethodCall();
    }

    /**
     * @param object $class
     *
     * @return ClassProxy
     */
    public static function proxy(object $class): ClassProxy
    {
        return new self($class);
    }

    /**
     * 添加宏
     *
     * @param string   $method
     * @param \Closure $closure
     *
     * @return void
     */
    public function macro(string $method, \Closure $closure)
    {
        $this->overwriteMethod[$method] = $closure->bindTo($this->class, $this->class);
    }

    /**
     * 是否是类的实例
     *
     * @param string{class-string} $class
     *
     * @return bool
     */
    public function instanceof(string $class): bool
    {
        return $this->class instanceof $class;
    }

    /**
     * 以代理类的作用域调用函数
     *
     * @param \Closure $closure
     *
     * @return mixed
     */
    public function call(\Closure $closure): mixed
    {
        return $closure->call($this->class);
    }

    /**
     * @param string $name
     *
     * @return false|mixed
     */
    public function __get(string $name)
    {
        return call_user_func($this->getter, $name);
    }

    public function __set(string $name, $value): void
    {
        call_user_func($this->setter, $name, $value);
    }

    public function __call(string $name, array $arguments)
    {
        if (isset($this->overwriteMethod[$name])) {
            return call_user_func($this->overwriteMethod[$name], ...$arguments);
        }

        return call_user_func($this->methodCall, $name, ...$arguments);
    }

    /**
     * @return void
     */
    private function bindPropertyGetter()
    {
        $getter = function (string $property) {
            return $this->$property;
        };

        $this->getter = $getter->bindTo($this->class, $this->class);
    }

    /**
     * @return void
     */
    private function bindPropertySetter()
    {
        $setter = function (string $property, mixed $value) {
            $this->$property = $value;
        };

        $this->setter = $setter->bindTo($this->class, $this->class);
    }

    /**
     * @return void
     */
    private function bindMethodCall()
    {
        $methodCall = function (string $method, ...$args) {
            return $this->$method(...$args);
        };

        $this->methodCall = $methodCall->bindTo($this->class, $this->class);
    }
}
