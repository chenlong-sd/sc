<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet;

use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\AccessToken;
use Sc\Util\Wechat\PublicPlatform\Applet\Data\AuthPhoneInfo;
use Sc\Util\Wechat\Request;

/**
 * Class PhoneNumber
 */
class PhoneNumber
{
    final const HOST = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=%s';

    public function __construct(protected Config $config){}

    /**
     * @param string $code
     *
     * @return array{"phoneNumber":string, "purePhoneNumber":string, "countryCode":int, "watermark":array{"timestamp":int, "appid":string}}
     * @throws WechatException|InvalidArgumentException
     */
    public function get(string $code): array
    {
        $response = Request::post(sprintf(self::HOST, AccessToken::get($this->config)), compact('code'));

        return $response->getData('phone_info') ?: [];
    }

    public function getAuthInfo(string $code): AuthPhoneInfo
    {
        return new AuthPhoneInfo($this->get($code));
    }
}
