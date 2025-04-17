<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\Applet\Data\AuthInfo;
use Sc\Util\Wechat\Request;

/**
 * Class Login
 */
class Login
{
    final const HOST = 'https://api.weixin.qq.com/sns/jscode2session';

    /**
     * @var array
     */
    protected array $data;

    /**
     * @param Config $config
     */
    public function __construct(protected Config $config)
    {
        $this->data = [
            'appid'      => $this->config->get('appid'),
            'secret'     => $this->config->get('secret'),
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * 获取用户信息
     *
     * @param string $code
     *
     * @return array{"openid":string,"session_key":string,"unionid":string,"errcode":int,"errmsg":string}
     * @throws WechatException
     */
    public function getInfo(string $code): array
    {
        $this->data['js_code'] = $code;

        $response = Request::get(sprintf('%s?%s', self::HOST, http_build_query($this->data)));

        return $response->getData();
    }

    /**
     * @param string $code
     * @return AuthInfo
     * @throws WechatException
     */
    public function getAuthInfo(string $code): AuthInfo
    {
        return new AuthInfo($this->getInfo($code));
    }
}
