<?php

namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;

use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * Class PromotionDetail
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/2 9:48
 */
class PromotionDetail
{
    use SetData;

    /**
     * 券ID
     * 示例值：109519
     *
     * @var string
     */
    public string $coupon_id = '';

    /**
     * 优惠名称
     * 示例值：单品惠-6
     * 优惠范围
     *
     * @var string
     */
    public string $name = '';

    /**
     * GLOBAL：全场代金券
     * SINGLE：单品优惠
     * 示例值：GLOBAL
     *
     * @var string
     */
    public string $scope = '';

    /**
     * 优惠类型
     * CASH：充值型代金券
     * NOCASH：免充值型代金券
     * 示例值：CASH
     *
     * @var string
     */
    public string $type = '';

    /**
     * 优惠券面额
     * 示例值：100
     *
     * @var int
     */
    public int $amount = 0;

    /**
     * @var string 活动ID
     * 示例值：931386
     */
    public string $stock_id = '';

    /**
     * 微信出资，单位为分
     * 示例值：0
     *
     * @var int
     */
    public int $wechatpay_contribute = 0;

    /**
     * 商户出资，单位为分
     * 示例值：0
     *
     * @var int
     */
    public int $merchant_contribute = 0;

    /**
     * 其他出资，单位为分
     * 示例值：0
     *
     * @var int
     */
    public int $other_contribute = 0;

    /**
     * 优惠币种
     * CNY：人民币，境内商户号仅支持人民币。
     * 示例值：CNY
     *
     * @var string
     */
    public string $currency = '';

    /**
     * 单品列表
     * @var array|GoodsDetail[]
     */
    public array $goods_detail = [];
}
