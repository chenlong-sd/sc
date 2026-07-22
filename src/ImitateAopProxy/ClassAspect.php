<?php

namespace Sc\Util\ImitateAopProxy;

/**
 * 类切片，使用AOP调用当前类的所有方法都会优先触发此切片方法，然后再触发调用方法的切片方法
 * - 方法权限为 private
 * - 方法参数：
 * - - array $params 原方法参数,
 * - - $callable 调用原方法的回调函数
 * - 调用原方法： $callback($params)
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ClassAspect
{

}