<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet;

use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\AccessToken;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Response;

/**
 * 下发小程序和公众号统一的服务消息
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/24 14:13
 */
class UniformMessage
{
    private const HOST = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=%s';

    /**
     * 发送的数据
     *
     * @var array
     */
    private array $data = [];

    public function __construct(protected Config $config)
    {
    }

    /**
     * 发到用户
     *
     * @param string $openid 用户的openID
     *
     * @return UniformMessage
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:23
     */
    public function toUser(string $openid): static
    {
        $this->data['openid'] = $openid;

        return $this;
    }

    /**
     * 设置公众号的APPID
     *
     * @param string $appid
     *
     * @return $this
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:36
     */
    public function publicAccountAppid(string $appid): static
    {
        $this->data['mp_template_msg']['appid'] = $appid;

        return $this;
    }

    /**
     * 跳转链接
     *
     * @param string $url 链接地址
     * @param array{"appid":string,"pagepath":string}  $appletInfo 跳转的小程序信息：appid, pagepath 页面地址
     *
     * @return UniformMessage
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:37
     */
    public function link(string $url, array $appletInfo = []): static
    {
        $this->data['mp_template_msg']['url']         = $url;
        $this->data['mp_template_msg']['miniprogram'] = $appletInfo;

        return $this;
    }

    /**
     * 发送消息
     *
     * @param string $templateId
     * @param array  $data
     *
     * @return Response
     *
     * @throws WechatException|InvalidArgumentException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:20
     */
    public function send(string $templateId, array $data)
    {
        $this->data['mp_template_msg']['template_id'] = $templateId;
        $this->data['mp_template_msg']['data']        = $data;

        return Request::post(sprintf(self::HOST, AccessToken::get($this->config)), $this->data);
    }
}
