<?php

namespace Sc\Util\Wechat\Pay\Type;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Pay\Type\Params\OptionalParameter;
use Sc\Util\Wechat\Pay\Type\Params\RequireParams;

/**
 * Class H5
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 14:07
 */
class H5
{
    const HOST = 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5';

    use OptionalParameter, RequireParams, DataHandle;

    public function __construct(protected Config $config)
    {
    }
}
