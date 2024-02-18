<?php

namespace Sc\Util\ImitateAopProxy\Interfaces;

/**
 * 切面注解接口
 *
 * Interface ImitateAspectInterface
 */
interface ImitateAspectAttrInterface
{
    /**
     * 获取对应切面
     *
     * @return ImitateAspectInterface
     */
    public function getImitateAspect(\ReflectionFunctionAbstract $reflectionFunctionAbstract): ImitateAspectInterface;
}
