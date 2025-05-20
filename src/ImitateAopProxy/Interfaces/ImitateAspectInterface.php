<?php

namespace Sc\Util\ImitateAopProxy\Interfaces;

/**
 * 切面接口
 *
 * Interface ImitateAspectInterface
 */
interface ImitateAspectInterface
{
    /**
     * 执行调用
     *
     * @param \Closure $closure
     *
     * @return mixed
     */
    public function handle(\Closure $closure, array $args): mixed;
}
