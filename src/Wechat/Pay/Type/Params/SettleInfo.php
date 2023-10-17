<?php
/**
 * datetime: 2021/9/8 10:29
 * user    : chenlong<vip_chenlong@163.com>
 **/


namespace Sc\Util\Wechat\Pay\Type\Params;

/**
 * 结算信息
 * Class SettleInfo
 *
 * @package App\Common\Wechat\apiv3\pay\place_param
 * @author  chenlong<vip_chenlong@163.com>
 * @date    2021/9/8
 */
class SettleInfo
{
    /**
     * @var bool 是否指定分账    profit_sharing    boolean    否    是否指定分账
     * 示例值：false
     */
    public bool $profit_sharing = false;

    /**
     * SettleInfo constructor.
     *
     * @param bool $profit_sharing 是否指定分账
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2021/9/8
     */
    public function __construct(bool $profit_sharing)
    {
        $this->profit_sharing = $profit_sharing;
    }
}

