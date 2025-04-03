<?php
/**
 * datetime: 2021/9/8 9:28
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;


/**
 * 优惠功能
 * Class Detail
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/8
 */
class Detail
{
    /**
     * 订单原价    cost_price    int    否    1、商户侧一张小票订单可能被分多次支付，订单原价用于记录整张小票的交易金额。
     * 2、当订单原价与支付金额不相等，则不享受优惠。
     * 3、该字段主要用于防止同一张小票分多次支付，以享受多次优惠的情况，正常支付订单不必上传此参数。
     * 示例值：608800
     *
     * @var int
     */
    public int $cost_price = 0;

    /**
     * 商品小票ID    invoice_id    string[1,32]    否    商家小票ID
     * 示例值：微信123
     *
     * @var string
     */

    public string $invoice_id = '';

    /**
     * -单品列表    goods_detail    array    否    单品列表信息
     * 对于json结构体参数，内层参数的必填属性仅在结构体有传参的情况下才生效，即如果json结构体都不传参，则内层参数必定全部都不传参。
     * 条目个数限制：【1，6000】
     *
     * @var array|GoodsDetail[]
     */
    public array $goods_detail = [];

}

