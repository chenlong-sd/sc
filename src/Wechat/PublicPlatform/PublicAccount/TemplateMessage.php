<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\AccessToken;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\TemplateMessage\Message;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Response;

/**
 * Class TemplateMessage
 */
class TemplateMessage
{
    const SEND_HOST = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s';

    public function __construct(protected Config $config){}


    /**
     * @param Message $message
     *
     * @return Response
     * @throws WechatException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function send(Message $message): Response
    {
        $url = sprintf(self::SEND_HOST, AccessToken::get($this->config));

        return Request::post($url, array_filter(json_decode(json_encode($message), true)));
    }

}