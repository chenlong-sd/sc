<?php

namespace Sc\Util\Wechat\Pay\Common\RefundData;

/**
 * Class Amount
 */
class Amount
{
    /**
     * 退款金额，单位为分，只能为整数，不能超过原订单支付金额。
     * 示例值：888
     *
     * @var int
     */
    public int $refund = 0;

    /**
     * 原订单金额
     *
     * 原支付交易的订单总金额，单位为分，只能为整数。
     * 示例值：888
     *
     * @var int
     */
    public int $total = 0;

    /**
     * 退款出资账户及金额
     *
     * 退款需要从指定账户出资时，传递此参数指定出资金额（币种的最小单位，只能为整数）。
     * 同时指定多个账户出资退款的使用场景需要满足以下条件：
     * 1、未开通退款支出分离产品功能；
     * 2、订单属于分账订单，且分账处于待分账或分账中状态。
     * 参数传递需要满足条件：
     * 1、基本账户可用余额出资金额与基本账户不可用余额出资金额之和等于退款金额；
     * 2、账户类型不能重复。
     * 上述任一条件不满足将返回错误
     *
     * @var array|AmountFrom[]
     */
    public array $from = [];

    /**
     * 退款币种
     *
     * 符合ISO 4217标准的三位字母代码，目前只支持人民币：CNY。
     * 示例值：CNY
     *
     * @var string
     */
    public string $currency = 'CNY';

    /**
     * @param int|float $refund 退款金额
     * @param int|float $total  原订单金额
     */
    public function __construct(int|float $refund, int|float $total = 0)
    {
        $this->refund = is_float($refund) ? (int)(round($refund, 2) * 100) : $refund;
        if ($total) {
            $this->total = is_float($total) ? (int)(round($total, 2) * 100) : $total;
        } else {
            $this->refund = $total;
        }
    }
}
