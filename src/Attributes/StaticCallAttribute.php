<?php
/**
 * datetime: 2022/2/20 15:13
 * user    : chenlong<vip_chenlong@163.com>
 **/

namespace Sc\Util\Attributes;

/**
 * 静态调用方法的注解类
 *
 * Class StaticCallAttribute
 * @package Sc\Util\Attributes
 * @author chenlong<vip_chenlong@163.com>
 * @date 2022/2/20
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class StaticCallAttribute
{
    /**
     * 类名称
     *
     * @var string
     */
    public string $classname;

    /**
     * 静态魔术方法
     *
     * @var string
     */
    public string $staticMagicMethod;

    /**
     * 获取实例的方法，默认为构造方法 __construct()
     *
     * @var string|null
     */
    private ?string $getInstanceStaticMethod;

    /**
     * StaticCallAttribute constructor.
     * @param string $staticMagicMethod
     * @param string $classname
     * @param string|null $getInstanceMethod
     * @author chenlong<vip_chenlong@163.com>
     * @date 2022/2/20
     */
    public function __construct(string $staticMagicMethod, string $classname, string $getInstanceMethod = null)
    {
        $this->staticMagicMethod = $staticMagicMethod;
        $this->classname         = $classname;
        $this->getInstanceStaticMethod = $getInstanceMethod;
    }

    /**
     * 获取工具类实例
     *
     * @param ...$args
     * @return mixed
     * @author chenlong<vip_chenlong@163.com>
     * @date 2022/2/20
     */
    public function getClass(...$args): mixed
    {
        return $this->getInstanceStaticMethod ?  forward_static_call("$this->classname::$this->getInstanceStaticMethod", ...$args)  : new $this->classname(...$args);
    }

    /**
     * 调用方法匹配
     *
     * @param string $method
     * @return bool
     * @author chenlong<vip_chenlong@163.com>
     * @date 2022/2/20
     */
    public function methodMatch(string $method): bool
    {
        return $this->staticMagicMethod === $method;
    }
}
