<?php

namespace Sc\Util\Wechat\Pay\Type\Params;

/**
 * Class RequireParams
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 15:05
 */
trait RequireParams
{
    /**
     * 应用ID    appid    string[1,32]    是    body 由微信生成的应用ID，全局唯一。请求基础下单接口时请注意APPID的应用属性，应为公众号的APPID
     * 示例值：wxd678efh567hg6787
     *
     * @var string
     */
    public string $appid = '';

    /**
     * 直连商户号    mchid    string[1,32]    是    body 直连商户的商户号，由微信支付生成并下发。
     * 示例值：1230000109
     *
     * @var string
     */
    public string $mchid = '';

    /**
     * 商品描述    description    string[1,127]    是    body 商品描述
     * 示例值：Image形象店-深圳腾大-QQ公仔
     *
     * @var string
     */
    public string $description = '';

    /**
     * 商户订单号    out_trade_no    string[6,32]    是    body 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
     * 示例值：1217752501201407033233368018
     *
     * @var string
     */
    public string $out_trade_no = '';


    /**
     * 通知地址    notify_url    string[1,256]    是    body 通知URL必须为直接可访问的URL，不允许携带查询串，要求必须为https地址。
     * 格式：URL
     * 示例值：https://www.weixin.qq.com/wxpay/pay.php
     *
     * @var string
     */
    public string $notify_url = '';

    /**
     *订单金额    amount    object    是    body 订单金额信息
     *
     * @var Amount|null
     */
    public ?Amount $amount = null;

}
