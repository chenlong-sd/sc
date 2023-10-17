<?php

namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;

use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * Class GoodsDetail
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/2 9:53
 */
class GoodsDetail
{
    use SetData;

    /**
     * 商品编码
     * 示例值：M1006
     *
     * @var string
     */
    public string $goods_id = '';

    /**
     * 用户购买的数量
     * 示例值：1
     *
     * @var int
     */
    public int $quantity = 0;

    /**
     * 商品单价，单位为分
     * 示例值：100
     *
     * @var int
     */
    public int $unit_price = 0;

    /**
     *商品优惠金额
     * 示例值：0
     *
     * @var int
     */
    public int $discount_amount = 0;
    /**
     * 商品备注信息
     * 示例值：商品备注信息
     *
     * @var string
     */
    public string $goods_remark = '';

}
