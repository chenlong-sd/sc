<?php

namespace Sc\Util\Wechat\Pay\Notice;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Pay\Notice\PayNoticeParams\PayNoticeData;

/**
 * Class PayNotice
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/1 16:32
 */
class PayNotice
{
    use ResolveData;

    /**
     * @var string 通知ID
     */
    public string $id = '';

    /**
     * @var string
     */
    public string $create_time = '';

    /**
     * @var string 通知数据类型
     */
    public string $resource_type = 'encrypt-resource';

    /**
     * @var string 通知类型
     */
    #[ExpectedValues(['TRANSACTION.SUCCESS'])]
    public string $event_type;

    /**
     * @var string 回调摘要
     */
    public string $summary = '';

    /**
     * 需要解密的原资源数据
     * @var array
     */
    #[ArrayShape([
        'original_type'   => 'string',
        'algorithm'       => 'string',
        'ciphertext'      => 'string32EtCS',
        'associated_data' => 'string',
        'nonce'           => 'string',
    ])]
    public array $resource = [];

    /**
     * 解密后的资源数据
     *
     * @var PayNoticeData
     */
    public PayNoticeData $data;


}
