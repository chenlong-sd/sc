<?php

namespace Sc\Util\Wechat;

use Sc\Util\Attributes\StaticCallAttribute;
use Sc\Util\StaticCall;
use Sc\Util\Wechat\PublicPlatform\Applet\Login;
use Sc\Util\Wechat\PublicPlatform\Applet\PhoneNumber;
use Sc\Util\Wechat\PublicPlatform\Applet\QRCode;
use Sc\Util\Wechat\PublicPlatform\Applet\UniformMessage;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\TemplateMessage;

/**
 * 微信公众号
 *
 * @method static TemplateMessage templateMessage(Config $config) 模板消息
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/24 14:15
 */
class WechatPublicAccount extends StaticCall
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
        return sprintf('Sc\\Util\\Wechat\\PublicPlatform\\PublicAccount\\%s', $shortClassName);
    }
}
