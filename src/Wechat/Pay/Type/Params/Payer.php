<?php
/**
 * datetime: 2021/9/8 0:04
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;


/**
 * 支付者
 * Class Payer
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author chenlong<vip_chenlong@163.com>
 * @date 2021/9/8
 */
class Payer
{
    /**
     * @var string
     */
    public string $openid = '';

    /**
     * Payer constructor.
     * @param string $openid
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/9/8
     */
    public function __construct(string $openid)
    {
        $this->openid = $openid;
    }
}

