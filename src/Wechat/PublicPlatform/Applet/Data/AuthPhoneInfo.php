<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet\Data;

use Sc\Util\Wechat\ArrayAccessData;

class AuthPhoneInfo implements \ArrayAccess
{
    use ArrayAccessData;

    public function getPhoneNumber()
    {
        return $this->getter('phoneNumber');
    }
    public function getPurePhoneNumber()
    {
        return $this->getter('purePhoneNumber');
    }
    public function getCountryCode()
    {
        return $this->getter('countryCode');
    }

    /**
     * @return int|null
     */
    public function getWatermarkTimestamp(): ?int
    {
        return $this->getter('watermark', [])['timestamp'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getWatermarkAppid(): ?string
    {
        return $this->getter('watermark', [])['appid'] ?? null;
    }
}