<?php
/**
 * datetime: 2021/9/7 21:50
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;

use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * 下单金额参数
 * Class Amount
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/7
 */
class Amount
{
    use SetData;
    /**
     * 总金额    total    int    是    订单总金额，单位为分。
     * 示例值：100
     *
     * @var int
     */
    public int $total = 0;

    /**
     * 用户支付金额，单位为分。
     * 示例值：100
     *
     * @var int
     */
    public int $payer_total = 0;

    /**
     * 货币类型    currency    string[1,16]    否    CNY：人民币，境内商户号仅支持人民币。
     * 示例值：CNY
     *
     * @var string
     */
    public string $currency = 'CNY';

    /**
     * 用户支付币种
     * 示例值：CNY
     *
     * @var string
     */
    public string $payer_currency = 'CNY';
}
