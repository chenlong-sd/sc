<?php

namespace Sc\Util\Wechat\Pay\Common;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\Pay\Common\RefundData\Amount;
use Sc\Util\Wechat\Pay\Common\RefundData\GoodsDetail;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Response;
use Sc\Util\Wechat\Tool;

/**
 * 退款
 *
 * Class Refund
 */
class Refund
{
    final const HOST = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds';

    /**
     * 微信支付订单号
     * 原支付交易对应的微信订单号
     * 示例值：1217752501201407033233368018
     *
     * @var string
     */
    public string $transaction_id = '';

    /**
     * 商户订单号
     *原支付交易对应的商户订单号
     * 示例值：1217752501201407033233368018
     *
     * @var string
     */
    public string $out_trade_no = '';

    /**
     * 商户退款单号
     * 商户系统内部的退款单号，商户系统内部唯一，只能是数字、大小写字母_-|*@ ，同一退款单号多次请求只退一笔。
     * 示例值：1217752501201407033233368018
     *
     * @var string
     */
    public string $out_refund_no = '';

    /**
     * 退款原因
     * 若商户传入，会在下发给用户的退款消息中体现退款原因
     * 示例值：商品已售完
     *
     * @var string
     */
    public string $reason = '';

    /**
     * 退款结果回调url
     *异步接收微信支付退款结果通知的回调地址，通知url必须为外网可访问的url，不能携带参数。 如果参数中传了notify_url，则商户平台上配置的回调地址将不会生效，优先回调当前传的这个地址。
     * 示例值：https://weixin.qq.com
     *
     * @var string
     */
    public string $notify_url = '';

    /**
     * 退款资金来源
     * 若传递此参数则使用对应的资金账户退款，否则默认使用未结算资金退款（仅对老资金流商户适用）
     * 枚举值：
     * AVAILABLE：可用余额账户
     * 示例值：AVAILABLE
     *
     * @var string
     */
    public string $funds_account = '';

    /**
     * -金额信息
     *
     * @var Amount|null
     */
    public ?Amount $amount = null;

    /**
     * 退款商品
     * 指定商品退款需要传此参数，其他场景无需传递
     *
     * @var array|GoodsDetail[]
     */
    public array $goods_detail = [];


    public function __construct(private Config $config)
    {
    }

    /**
     * 申请退款
     *
     * @return Response
     * @throws WechatException
     */
    public function apply(): Response
    {
        $data = json_decode(json_encode($this), true);
        $data = $this->getData($data);

        $sign = Tool::config($this->config)->v3Sign(self::HOST, $data, 'POST');

        return Request::postV3(self::HOST, $data, $sign);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function getData(array $data): array
    {
        foreach ($data as &$datum) {
            if (is_array($datum)) {
                $datum = $this->getData($datum);
            }
        }

        return array_filter($data, function ($v){
            return $v || (is_numeric($v) && $v == 0);
        });
    }
}
