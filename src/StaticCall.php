<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Sc\Util;

use Sc\Util\Attributes\StaticCallAttribute;

/**
 * 静态调用实现.
 *
 * Class StaticCall
 * @date 2022/2/20
 */
abstract class StaticCall
{
    /**
     * 已调用过的类.
     */
    private static array $calledClass = [];

    /**
     * 是否获取新的实例.
     */
    private static bool $isNew = true;

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @date 2022/2/20
     */
    public static function __callStatic($name, $arguments)
    {
        return self::callClass($name, $arguments);
    }

    /**
     * @return mixed
     * @date 2022/2/20
     */
    public function __call(string $name, array $arguments)
    {
        return self::callClass($name, $arguments);
    }

    /**
     * 调用新的类.
     *
     * @date 2022/2/20
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * 获取累的完全限定名称.
     *
     * @date 2022/2/20
     */
    abstract protected static function getClassFullyQualifiedName(string $shortClassName): string;

    /**
     * 是否返回新实例.
     */
    protected static function isNewInstance(): bool
    {
        return self::$isNew;
    }

    /**
     * 获取调用类.
     *
     * @date 2022/2/20
     */
    private static function getClass(string $method, array $args = []): mixed
    {
        return self::$calledClass[static::class . '@' . $method]
            = new (static::getClassFullyQualifiedName(ucfirst($method)))(...$args);
    }

    /**
     * 调用类.
     *
     * @date 2022/2/20
     */
    private static function callClass(string $name, array $arguments): mixed
    {
        if (($oldClass = self::oldClass($name)) && ! static::isNewInstance()) {
            return $oldClass;
        }

        return self::makeClassInstance($name, $arguments);
    }

    /**
     * 返回已经调用过的类.
     *
     * @date 2022/2/20
     */
    private static function oldClass(string $method): mixed
    {
        return self::$calledClass[static::class . '@' . $method] ?? null;
    }

    /**
     * 解析出调用的类，并实例化.
     *
     * @date 2022/2/20
     */
    private static function makeClassInstance(string $name, array $args = []): mixed
    {
        $selfReflex = new \ReflectionClass(static::class);

        $attributes = $selfReflex->getAttributes(StaticCallAttribute::class);

        foreach ($attributes as $attribute) {
            /**
             * @var StaticCallAttribute $staticCallAttribute
             */
            $staticCallAttribute = $attribute->newInstance();
            if ($staticCallAttribute->methodMatch($name)) {
                return self::$calledClass[static::class . '@' . $name] = $staticCallAttribute->getClass(...$args);
            }
        }

        return self::getClass($name, $args);
    }
}
