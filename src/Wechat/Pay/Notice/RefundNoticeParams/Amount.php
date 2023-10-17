<?php
/**
 * datetime: 2023/2/15 0:28
 **/

namespace Sc\Util\Wechat\Pay\Notice\RefundNoticeParams;

use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * Class Amount
 *
 * @package Sc\Util\Wechat\Pay\Notice\RefundNoticeParams
 * @date    2023/2/15
 */
class Amount
{
    use SetData;

    /**
     * 订单金额
     * 订单总金额，单位为分，只能为整数，详见支付金额
     * 示例值：999
     *
     * @var int
     */
    public int $total = 0;

    /**
     * 退款金额
     * 退款金额，币种的最小单位，只能为整数，不能超过原订单支付金额，如果有使用券，后台会按比例退。
     * 示例值：999
     *
     * @var int
     */
    public int $refund = 0;

    /**
     * 用户支付金额
     * 用户实际支付金额，单位为分，只能为整数，详见支付金额
     * 示例值：999
     *
     * @var int
     */
    public int $payer_total = 0;

    /**
     * 用户退款金额
     * 退款给用户的金额，不包含所有优惠券金额
     * 示例值：999
     *
     * @var int
     */
    public int $payer_refund = 0;
}

