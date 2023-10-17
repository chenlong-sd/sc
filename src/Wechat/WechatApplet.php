<?php

namespace Sc\Util\Wechat;

use Sc\Util\Attributes\StaticCallAttribute;
use Sc\Util\StaticCall;
use Sc\Util\Wechat\PublicPlatform\Applet\Login;
use Sc\Util\Wechat\PublicPlatform\Applet\PhoneNumber;
use Sc\Util\Wechat\PublicPlatform\Applet\QRCode;
use Sc\Util\Wechat\PublicPlatform\Applet\UniformMessage;

/**
 * 微信小程序
 *
 * @method static UniformMessage uniformMessage() 统一服务消息
 * @method static Login login(Config $config) 登录
 * @method static PhoneNumber phone(Config $config) 手机号
 * @method static QRCode QRCode(Config $config) 小程序码
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/24 14:15
 */
#[StaticCallAttribute('uniformMessage', UniformMessage::class)]
#[StaticCallAttribute('login', Login::class)]
#[StaticCallAttribute('phone', PhoneNumber::class)]
#[StaticCallAttribute('QRCode', QRCode::class)]
class WechatApplet extends StaticCall
{
    /**
     * 获取类的全命名空间
     *
     * @param string $shortClassName
     *
     * @return string
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:17
     */
    protected static function getClassFullyQualifiedName(string $shortClassName): string
    {
        return sprintf('Sc\\Util\\Wechat\\PublicPlatform\\Applet\\%s', $shortClassName);
    }
}
