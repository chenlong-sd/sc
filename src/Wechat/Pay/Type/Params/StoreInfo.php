<?php
/**
 * datetime: 2021/9/8 10:22
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;


/**
 * 商户门店信息
 * Class StoreInfo
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/8
 */
class StoreInfo
{

    /**
     * 门店编号    id    string[1,32]    是    商户侧门店编号
     * 示例值：0001
     *
     * @var string
     */
    public string $id = '';

    /**
     * 门店名称    name    string[1,256]    否    商户侧门店名称
     * 示例值：腾讯大厦分店
     *
     * @var string
     */
    public string $name = '';

    /**
     * 地区编码    area_code    string[1,32]    否    地区编码，详细请见省市区编号对照表。
     * 示例值：440305
     *
     * @var string
     */
    public string $area_code = '';

    /**
     * 详细地址    address    string[1,512]    否    详细的商户门店地址
     * 示例值：广东省深圳市南山区科技中一道10000号
     *
     * @var string
     */
    public string $address = '';


    /**
     * StoreInfo constructor.
     *
     * @param string $id
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2021/9/8
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

