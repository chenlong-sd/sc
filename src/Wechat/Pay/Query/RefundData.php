<?php

namespace Sc\Util\Wechat\Pay\Query;

use Sc\Util\Wechat\Pay\AbstractDataGetter;
use Sc\Util\Wechat\Pay\Query\Refund\Amount;
use Sc\Util\Wechat\Pay\Query\Refund\PromotionDetail;

/**
 * Class RefundData
 * @property-read Amount $amount
 * @property-read string $channel
 * @property-read string $create_time
 * @property-read string $funds_account
 * @property-read string $out_refund_no
 * @property-read PromotionDetail $promotion_detail
 * @property-read string $refund_id
 * @property-read string $status
 * @property-read string $success_time
 * @property-read string $transaction_id
 * @property-read string $user_received_account
 */
class RefundData extends AbstractDataGetter
{
    protected function ObjectMap(): array
    {
        return [
            'amount'           => Amount::class,
            'promotion_detail' => PromotionDetail::class
        ];
    }
}