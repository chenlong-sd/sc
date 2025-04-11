<?php

namespace Sc\Util\Wechat\Pay\Notice\RefundNoticeParams;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * 退货通知解密后的数据
 *
 * Class RefundNoticeData
 */
class RefundNoticeData
{
    use SetData;

    /**
     * 服务商户号
     *
     * @var string
     */
    public string $sp_mchid = '';

    /**
     * 子商户号
     *
     * @var string
     */
    public string $sub_mchid = '';

    /**
     * 商户订单号
     *
     * 返回的商户订单号
     * 示例值： 1217752501201407033233368018
     *
     * @var string
     */

    public string $out_trade_no = '';

    /**
     * 微信支付订单号
     *
     * 微信支付订单号
     * 示例值： 1217752501201407033233368018
     *
     * @var string
     */
    public string $transaction_id = '';

    /**
     * 商户退款单号
     *
     * 商户退款单号
     * 示例值： 1217752501201407033233368018
     *
     * @var string
     */
    public string $out_refund_no = '';

    /**
     * 微信支付退款单号
     *
     * 微信退款单号
     * 示例值： 1217752501201407033233368018
     *
     * @var string
     */
    public string $refund_id = '';

    /**
     * 退款状态
     *
     * 退款状态，枚举值：
     * SUCCESS：退款成功
     * CLOSE：退款关闭
     * ABNORMAL：退款异常，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败，可前往【服务商平台—>交易中心】，手动处理此笔退款
     * 示例值：SUCCESS
     *
     * @var string
     */
    #[ExpectedValues(['SUCCESS', 'CLOSE', 'ABNORMAL'])]
    public string $refund_status = '';

    /**
     * 退款成功时间
     *
     * 1、退款成功时间，遵循rfc3339标准格式，格式为yyyy-MM-DDTHH:mm:ss+TIMEZONE，yyyy-MM-DD表示年月日，T出现在字符串中，表示time元素的开头，HH:mm:ss表示时分秒，TIMEZONE表示时区（+08:00表示东八区时间，领先UTC 8小时，即北京时间）。例如：2015-05-20T13:29:35+08:00表示，北京时间2015年5月20日13点29分35秒。
     * 2、当退款状态为退款成功时返回此参数。
     * 示例值：2018-06-08T10:34:56+08:00
     *
     * @var string
     */
    public string $success_time = '';
    /**
     * 退款入账账户
     *
     * 取当前退款单的退款入账方。
     * 退回银行卡：{银行名称}{卡类型}{卡尾号}
     * 退回支付用户零钱: 支付用户零钱
     * 退还商户: 商户基本账户/商户结算银行账户
     * 退回支付用户零钱通：支付用户零钱通
     * 示例值：招商银行信用卡0403
     *
     * @var string
     */
    public string $user_received_account = '';

    /**
     * 金额信息
     *
     * @var Amount
     */
    public Amount $amount;
}
