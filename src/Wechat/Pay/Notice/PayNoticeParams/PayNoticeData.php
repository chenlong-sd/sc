<?php

namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * 通知解密后的数据
 *
 * Class PayNoticeData
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/2 9:16
 */
class PayNoticeData
{
    use SetData;

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
     * 支付者
     *
     * @var Payer|null
     */
    public ?Payer $payer = null;

    /**
     * 微信支付订单号
     * @var string
     */
    public string $transaction_id = '';

    /**
     * 商户订单号
     *
     * @var string
     */
    public string $out_trade_no = '';

    /**
     * 交易类型
     * JSAPI：公众号支付
     * NATIVE：扫码支付
     * APP：APP支付
     * MICROPAY：付款码支付
     * MWEB：H5支付
     * FACEPAY：刷脸支付
     *
     * @var string
     */
    #[ExpectedValues(['JSAPI', 'NATIVE', 'APP', 'MICROPAY', 'MWEB', 'FACEPAY',])]
    public string $trade_type = '';

    /**
     * 交易状态，枚举值：
     * SUCCESS：支付成功
     * REFUND：转入退款
     * NOTPAY：未支付
     * CLOSED：已关闭
     * REVOKED：已撤销（付款码支付）
     * USERPAYING：用户支付中（付款码支付）
     * PAYERROR：支付失败(其他原因，如银行返回失败)
     *
     * @var string
     */
    #[ExpectedValues(['SUCCESS', 'REFUND', 'NOTPAY', 'CLOSED', 'REVOKED', 'USERPAYING', 'PAYERROR',])]
    public string $trade_state = '';

    /**
     * 交易状态描述
     * @var string
     */
    public string $trade_state_desc = '';

    /**
     * 付款银行
     *
     * @var string
     */
    public string $bank_type = '';

    /**
     * 支付完成时间
     *
     * @var string
     */
    public string $success_time = '';

    /**
     * 订单金额
     * @var Amount|null
     */
    public ?Amount $amount = null;

    /**
     * 优惠功能
     * @var PromotionDetail|null
     */
    public ?PromotionDetail $promotion_detail = null;

    /**
     * 场景信息
     * @var SceneInfo|null
     */
    public ?SceneInfo $scene_info = null;

    /**
     * 附加数据
     *
     * @var string
     */
    public string $attach = '';
}
