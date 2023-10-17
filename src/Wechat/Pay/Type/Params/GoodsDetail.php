<?php
/**
 * datetime: 2021/9/8 10:00
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;

/**
 * 单品商品信息
 * Class GoodsDetail
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/8
 */
class GoodsDetail
{

    /**
     * 商户侧商品编码    merchant_goods_id    string[1,32]    是    由半角的大小写字母、数字、中划线、下划线中的一种或几种组成。
     * 示例值：1246464644
     *
     * @var string
     */
    public string $merchant_goods_id;

    /**
     * 微信支付商品编码    Wechatpay_goods_id    string[1,32]    否    微信支付定义的统一商品编号（没有可不传）
     * 示例值：1001
     *
     * @var string
     */
    public string $Wechatpay_goods_id;

    /**
     * 商品名称    goods_name    string[1,256]    否    商品的实际名称
     * 示例值：iPhoneX 256G
     *
     * @var string
     */
    public string $goods_name;

    /**
     * 商品数量    quantity    int    是    用户购买的数量
     * 示例值：1
     *
     * @var int
     */
    public int $quantity;

    /**
     * 商品单价    unit_price    int    是    商品单价，单位为分
     * 示例值：828800
     *
     * @var int
     */
    public int $unit_price;


    /**
     * GoodsDetail constructor.
     *
     * @param string $goods_name
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2021/9/8
     */
    public function __construct(string $goods_name = '')
    {
        $goods_name and $this->goods_name = $goods_name;
    }
}

