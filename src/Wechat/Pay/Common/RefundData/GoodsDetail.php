<?php

namespace Sc\Util\Wechat\Pay\Common\RefundData;

/**
 * Class GoodsDetail
 */
class GoodsDetail
{
    /**
     * 商户侧商品编码
     * 由半角的大小写字母、数字、中划线、下划线中的一种或几种组成
     * 示例值：1217752501201407033233368018
     *
     * @var string
     */
    public string $merchant_goods_id = '';

    /**
     * 微信支付商品编码
     * 微信支付定义的统一商品编号（没有可不传）
     * 示例值：1001
     *
     * @var string
     */
    public string $wechatpay_goods_id = '';

    /**
     * 商品名称
     *商品的实际名称
     * 示例值：iPhone6s 16G
     *
     * @var string
     */
    public string $goods_name = '';

    /**
     *商品单价
     *商品单价金额，单位为分
     * 示例值：528800
     *
     * @var string
     */
    public string $unit_price = '';

    /**
     * 商品退款金额
     * 商品退款金额，单位为分
     * 示例值：528800
     *
     * @var string
     */
    public string $refund_amount = '';

    /**
     * 商品退货数量
     * 单品的退款数量
     * 示例值：1
     *
     * @var string
     */
    public string $refund_quantity = '';

}
