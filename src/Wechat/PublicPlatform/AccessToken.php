<?php

namespace Sc\Util\Wechat\PublicPlatform;

use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Tool;

/**
 * Class AccessToken
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/22 18:11
 */
class AccessToken
{

    /**
     * 请求地址
     */
    private const HOST = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';

    /**
     * 获取token
     *
     * @return array|void
     * @throws WechatException|InvalidArgumentException
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:02
     */
    public static function get(Config $config)
    {
        $cache = Tool::cache($config->get('cacheEngine') ?: null);
        $key   = "WT1:{$config->get('appid')}";
        if ($accessToken = $cache->get($key)) {
            return $accessToken;
        }

        $response = Request::get(sprintf(self::HOST, $config->get('appid'), $config->get('secret')));

        if (!$response->getData('access_token')) {
            throw new WechatException("获取token失败");
        }

        $cache->set($key, $response->getData('access_token'), 6500);

        return $response->getData('access_token');
    }
}
