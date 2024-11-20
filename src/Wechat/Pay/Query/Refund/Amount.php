<?php

namespace Sc\Util\Wechat\Pay\Query\Refund;

use Sc\Util\Wechat\Pay\AbstractDataGetter;

/**
 * Class Amount
 * @property-read string $currency 退款币种
 * @property-read int $discount_refund 优惠退款金额
 * @property-read array $from 退款出资账户及金额
 * @property-read int $payer_refund 用户退款金额
 * @property-read int $payer_total 用户支付金额
 * @property-read int $refund 退款金额
 * @property-read int $refund_fee 手续费退款金额
 * @property-read int $settlement_refund 应结退款金额
 * @property-read int $settlement_total 应结订单金额
 * @property-read int $total 订单金额
 */
class Amount extends AbstractDataGetter
{

}