<?php

namespace Sc\Util\Wechat;

use Sc\Util\Attributes\StaticCallAttribute;
use Sc\Util\StaticCall;
use Sc\Util\Wechat\Pay\Common;
use Sc\Util\Wechat\Pay\Notice\RefundNotice;
use Sc\Util\Wechat\Pay\Type\Applet;
use Sc\Util\Wechat\Pay\Type\H5;
use Sc\Util\Wechat\Pay\Notice\PayNotice;
use Sc\Util\Wechat\Pay\Type\JsApi;

/**
 * 微信支付
 * Class WechatPay
 *
 * @method static H5 h5(Config $config) H5支付
 * @method static Applet applet(Config $config) 小程序支付
 * @method static JsApi jsApi(Config $config) jsApi支付
 * @method static Common common(Config $config) 其他通用接口，如查询， 关单 。。。
 * @method static PayNotice getNoticeData(Config $config, array $data = [])
 * @method static RefundNotice getRefundNoticeData(Config $config, array $data = [])
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 15:57
 */
#[StaticCallAttribute('common', Common::class)]
#[StaticCallAttribute('getNoticeData', PayNotice::class)]
#[StaticCallAttribute('getRefundNoticeData', RefundNotice::class)]
class WechatPay extends StaticCall
{
    protected static function getClassFullyQualifiedName(string $shortClassName): string
    {
        return "Sc\\Util\\Wechat\\Pay\\Type\\$shortClassName";
    }
}
