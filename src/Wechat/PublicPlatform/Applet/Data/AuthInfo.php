<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet\Data;

use Sc\Util\Wechat\ArrayAccessData;

class AuthInfo implements \ArrayAccess
{
    use ArrayAccessData;

    public function getOpenid(): string
    {
        return $this->getter('openid');
    }

    public function getSessionKey(): string
    {
        return $this->getter('session_key');
    }

    public function getUnionid(): string
    {
        return $this->getter('unionid');
    }

    public function getErrcode(): int
    {
        return $this->getter('errcode');
    }

    public function getErrmsg(): string
    {
        return $this->getter('errmsg');
    }

}