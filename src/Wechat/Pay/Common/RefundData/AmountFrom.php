<?php

namespace Sc\Util\Wechat\Pay\Common\RefundData;

/**
 * Class From
 */
class AmountFrom
{
    /**
     * 出资账户类型
     * 下面枚举值多选一。
     * 枚举值：
     * AVAILABLE : 可用余额
     * UNAVAILABLE : 不可用余额
     * 示例值：AVAILABLE
     *
     * @var string
     */
    public string $account = '';


    /**
     * 对应账户出资金额
     * 示例值：444
     *
     * @var int
     */
    public int $amount = 0;
}
