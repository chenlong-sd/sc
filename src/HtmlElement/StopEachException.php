<?php
/**
 * datetime: 2023/4/18 1:22
 **/

namespace Sc\Util\HtmlElement;

/**
 * Class StopEachException
 *
 * @package Sc\Util\HtmlElement
 * @date    2023/5/4
 */
class StopEachException extends \Exception
{
    /**
     * @param bool $throwException
     *
     * @return void
     * @throws StopEachException
     * @date 2023/5/4
     */
    public function stop(bool $throwException = true): void
    {
        $throwException and throw new self();
    }
}