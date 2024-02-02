<?php

namespace Sc\Util\Wechat\Pay\Type;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Pay\Type\Params\OptionalParameter;
use Sc\Util\Wechat\Pay\Type\Params\Payer;
use Sc\Util\Wechat\Pay\Type\Params\RequireParams;

/**
 * Class Applet
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 14:07
 */
class Applet
{
    const HOST = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

    use OptionalParameter, RequireParams, DataHandle;

    /**
     * 支付者
     *
     * @var Payer|null
     */
    public ?Payer $payer = null;

    /**
     * 电子发票入口开放标识
     * 传入true时，支付成功消息和支付详情页将出现开票入口。需要在微信支付商户平台或微信公众平台开通电子发票功能，传此字段才可生效。
     * true：是
     * false：否
     *
     * @var bool
     */
    public bool $support_fapiao = false;

    public function __construct(protected Config $config)
    {
    }
}
