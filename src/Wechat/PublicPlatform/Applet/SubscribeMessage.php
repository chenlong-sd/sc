<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet;

use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\AccessToken;
use Sc\Util\Wechat\PublicPlatform\Applet\SubscribeMessage\Message;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Response;

/**
 * 订阅消息
 *
 * Class SubscribeMessage
 */
class SubscribeMessage
{
    const HOST = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=%s';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Message $message
     *
     * @return Response
     * @throws InvalidArgumentException
     * @throws WechatException
     */
    public function send(Message $message): Response
    {
        $url = sprintf(self::HOST, AccessToken::get($this->config));

        return Request::post($url, array_filter(json_decode(json_encode($message), true)));
    }
}