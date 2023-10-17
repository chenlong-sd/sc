<?php
/**
 * datetime: 2021/9/8 10:21
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;

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


    /**
     * 用户终端IP    payer_client_ip    string[1,45]    是    用户的客户端IP，支持IPv4和IPv6两种格式的IP地址。
     * 示例值：14.23.150.211
     *
     * @var string
     */
    public string $payer_client_ip = '';

    /**
     * 商户端设备号    device_id    string[1,32]    否    商户端设备号（门店号或收银设备ID）。
     * 示例值：013467007045764
     *
     * @var string
     */
    public string $device_id = '';

    /**
     * -商户门店信息    store_info    object    否    商户门店信息
     * 对于json结构体参数，内层参数的必填属性仅在结构体有传参的情况下才生效，即如果json结构体都不传参，则内层参数必定全部都不传参。
     *
     * @var StoreInfo|null
     */
    public ?StoreInfo $store_info = null;

    /**
     * H5场景信息	h5_info	object	是	H5场景信息
     * @var H5Info|null
     */
    public ?H5Info $h5_info = null;

    /**
     * SceneInfo constructor.
     *
     * @param string $payer_client_ip
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2021/9/8
     */
    public function __construct(string $payer_client_ip)
    {
        $this->payer_client_ip = $payer_client_ip;
    }
}
