<?php

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\Pay\Params\SceneInfo;

// 配置方式一
$config = Config::base('appid', 'secret')
    // 支付配置
    ->payConfig('mch_id', 'key', 'serial_no', 'cert_path');

// 配置方式二
$config = Config::base([
    'appid'  => '',
    'secret' => ''
])->payConfig([ // 支付配置
    'mch_id'    => '',
    'key'       => '',
    'serial_no' => '',
    'cert_path' => 'app/Common/Wechat'
]);

/**
 * H5 支付示例
 * 支付须提前配置支付相关配置
 */
$h5 = \Sc\Util\Wechat\WechatPay::h5($config);

$h5->notify_url  = sprintf("https://%s", $_SERVER['HTTP_HOST']);
$h5->amount      = new \Sc\Util\Wechat\Pay\Params\Amount(1);
$h5->description = 'test';
$h5->scene_info  = new SceneInfo('127.0.0.1');

try {
    $pay_data = $h5->getPayData();

    // TODO 业务处理

} catch (WechatException $e) {
    // TODO 支付出现错误
}

