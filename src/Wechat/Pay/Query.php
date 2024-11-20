<?php

namespace Sc\Util\Wechat\Pay;

use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Pay\Query\RefundData;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Tool;

/**
 * Class Query
 */
class Query
{
    public function __construct(private Config $config)
    {}


    public function return(string $out_refund_no): RefundData
    {
        $url = "https://api.mch.weixin.qq.com/v3/refund/domestic/refunds/$out_refund_no";

        $v3Sign = Tool::config($this->config)->v3Sign($url);

        $res = Request::getV3($url, $v3Sign);

        if (empty($res->getData('code'))) {
            return new RefundData($res->getData());
        }
        throw new WechatException($res['message'] ?? json_encode($res));
    }
}