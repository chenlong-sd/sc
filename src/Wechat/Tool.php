<?php

namespace Sc\Util\Wechat;

/**
 * Class Tool
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 15:15
 */
class Tool
{
    public function __construct(protected Config $config)
    {
    }

    public static function config(Config $config): Tool
    {
        return new self($config);
    }

    /**
     * 支付 api v3签名
     *
     * @param string $url
     * @param array  $body
     * @param string $method
     *
     * @return string
     */
    public function v3Sign(string $url, array $body = [], string $method = 'GET'): string
    {
        $urlParts     = parse_url($url);
        $canonicalUrl = $urlParts['path'] . (!empty($urlParts['query']) ? "?{$urlParts['query']}" : "");
        $signArr      = [
            'method'    => $method,
            'uri'       => $canonicalUrl,
            'timestamp' => time(),
            'nonce_str' => md5(microtime()),
            'body'      => $method === 'GET' ? '' : json_encode($body)
        ];

        $signature    = $this->getSignature($signArr);

        $replaceValue = [
            $this->config->get('mch_id'),
            $signArr['nonce_str'],
            $signArr['timestamp'],
            $this->config->get('serial_no'),
            $signature
        ];

        $str = 'mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"';

        return sprintf($str, ...$replaceValue);
    }


    /**
     * 判断是否是是https
     *
     * @return bool
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/27 11:12
     */
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }

    /**
     * @param string|array $signData
     *
     * @return string
     */
    public function getSignature(string|array $signData): string
    {
        $signStr = is_array($signData) ? implode("\n", $signData) . "\n" : $signData;

        openssl_sign($signStr, $signature, openssl_get_privatekey($this->config->get('cert_key')), 'sha256WithRSAEncryption');

        return base64_encode($signature);
    }

    /**
     * 缓存
     *
     * @param mixed|null $cacheEngine
     *
     * @return Cache
     */
    public static function cache(mixed $cacheEngine = null): Cache
    {
        return new Cache($cacheEngine);
    }
}
