<?php
/**
 * datetime: 2021/9/7 21:50
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;

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

    /**
     * 总金额    total    int    是    订单总金额，单位为分。
     * 示例值：100
     *
     * @var int
     */
    public int $total = 0;

    /**
     * 货币类型    currency    string[1,16]    否    CNY：人民币，境内商户号仅支持人民币。
     * 示例值：CNY
     *
     * @var string
     */
    public string $currency = 'CNY';


    /**
     * Amount constructor.
     *
     * @param int|float $total 总金额
     * @param string    $currency 货币类型
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2021/9/7
     */
    public function __construct(int|float $total, string $currency = 'CNY')
    {
        $this->total    = (int)(round($total, 2) * 100);
        $this->currency = $currency;
    }
}
