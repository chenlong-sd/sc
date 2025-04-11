<?php

namespace Sc\Util\Wechat\Pay\Type\Params;

/**
 * Trait OptionalParameter
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 15:09
 */
trait OptionalParameter
{
    /**
     * 交易结束时间    time_expire    string[1,64]    否    body 订单失效时间，遵循rfc3339标准格式，格式为yyyy-MM-DDTHH:mm:ss+TIMEZONE，yyyy-MM-DD表示年月日，T出现在字符串中，表示time元素的开头，HH:mm:ss表示时分秒，TIMEZONE表示时区（+08:00表示东八区时间，领先UTC8小时，即北京时间）。例如：2015-05-20T13:29:35+08:00表示，北京时间2015年5月20日 13点29分35秒。
     * 示例值：2018-06-08T10:34:56+08:00
     *
     * @var string
     */
    public string $time_expire = '';

    /**
     * 附加数据    attach    string[1,128]    否    body 附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用
     * 示例值：自定义数据
     *
     * @var string
     */
    public string $attach = '';


    /**
     * 订单优惠标记    goods_tag    string[1,32]    否    body 订单优惠标记
     * 示例值：WXG
     *
     * @var string
     */
    public string $goods_tag = '';


    /**
     * 优惠功能    detail    object    否    body 优惠功能
     *
     * @var Detail|null
     */
    public ?Detail $detail = null;

    /**
     *
     * 场景信息    scene_info    object    是    body 支付场景描述
     *
     * @var SceneInfo|null
     */
    public ?SceneInfo $scene_info = null;

    /**
     * 结算信息	settle_info	object	否	body 结算信息
     *
     * @var SettleInfo|null
     */
    public ?SettleInfo $settle_info = null;

}
