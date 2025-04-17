<?php
/**
 * datetime: 2021/9/8 0:04
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;


use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * 支付者
 * Class Payer
 *
 * @property string $openid
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author chenlong<vip_chenlong@163.com>
 * @date 2021/9/8
 */
class Payer
{
    use SetData;

    /**
     * @var string
     */
    public string $openid = '';
}

