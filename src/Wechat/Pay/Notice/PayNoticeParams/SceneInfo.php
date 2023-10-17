<?php
/**
 * datetime: 2021/9/8 10:21
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Notice\PayNoticeParams;

use Sc\Util\Wechat\Pay\Notice\SetData;

/**
 * 场景信息
 * Class SceneInfo
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/8
 */
class SceneInfo
{
    use SetData;

    /**
     * 商户端设备号    device_id    string[1,32]    否    商户端设备号（门店号或收银设备ID）。
     * 示例值：013467007045764
     *
     * @var string
     */
    public string $device_id = '';
}
