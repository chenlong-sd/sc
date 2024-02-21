<?php

namespace Sc\Util\Wechat;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class Config
 *
 * @property-read string $mch_id         商户号
 * @property-read string $key            支付密钥
 * @property-read string $serial_no      支付序列号
 * @property-read string $appid
 * @property-read string $secret
 * @property-read string $cacheEngine    缓存静态调用类
 * @property-read string $cert           证书路径
 * @property-read string $cert_key       证书密钥路径
 * @property-read string $pub_token      公众号域名配置token
 * @property-read string $EncodingAESKey 公众号消息加解密密钥
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 15:21
 */
class Config
{
    /**
     * @var array
     */
    private array $data = [
        'mch_id'         => '', // 商户号
        'key'            => '', // 支付密钥
        'v3_key'         => '', // v3支付密钥
        'serial_no'      => '', // 支付序列号
        'appid'          => '', //
        'secret'         => '', //
        'cacheEngine'    => '', // 缓存静态调用类
        'cert'           => '', // 证书内容
        'cert_key'       => '', // 证书密钥内容
        'pub_token'      => '', // 公众号域名验证token
        'EncodingAESKey' => '', // 消息加解密密钥
    ];

    /**
     * @param array|string $attr
     * @param string|null  $value
     *
     * @return Config
     */
    public static function init(#[ExpectedValues(['mch_id', 'key', 'v3_key', 'cert_path', 'serial_no', 'appid', 'secret', 'cacheEngine'])] array|string $attr, string $value = null): Config
    {
        return (new self())->set($attr, $value);
    }


    /**
     * 基础设置
     *
     * @param string|array $appid
     * @param string       $secret
     *
     * @return Config
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/26 15:24
     */
    public static function base(string|array $appid, string $secret = ''): Config
    {
        $config = new self();

        if (is_array($appid)) extract($appid);

        return $config->set(array_filter(func_get_args()));
    }

    /**
     * 支付设置
     *
     * @param string|array $mch_id    商户号
     * @param string       $key       商户密钥
     * @param string       $v3Key     商户V3密钥
     * @param string       $serial_no 证书序列号
     * @param string       $cert_path 证书所在文件夹
     *
     * @return Config
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/26 15:31
     */
    public function payConfig(string|array $mch_id, string $key = '', string $v3Key = '', string $serial_no = '', string $cert_path = ''): static
    {
        if (is_array($mch_id)) extract($mch_id);

        return $this->set(array_filter(func_get_args()));
    }

    /**
     * 获取配置
     *
     * @param string $key
     *
     * @return mixed|null
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/26 15:35
     */
    public function get(#[ExpectedValues(['mch_id', 'key', 'v3_key', 'serial_no', 'cert', 'cert_key', 'appid', 'secret', 'cacheEngine'])]
                               string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * 配置指定项
     *
     * @param array|string $attr
     * @param string|null  $value
     *
     * @return Config
     */
    public function set(#[ExpectedValues(['mch_id', 'cert_path', 'key', 'v3_key', 'serial_no', 'appid', 'secret', 'cacheEngine'])] array|string $attr, string $value = null): static
    {
        if (is_string($attr)) {
            $attr = [$attr => $value];
        }

        foreach ($attr as $key => $val){
            $this->data[$key] = $val;
            if ($key == "cert_path") {
                if (is_file($val . DIRECTORY_SEPARATOR . 'apiclient_cert.pem')) {
                    $this->data['cert']     = file_get_contents($val . DIRECTORY_SEPARATOR . 'apiclient_cert.pem');
                }

                if (is_file($val . DIRECTORY_SEPARATOR . 'apiclient_key.pem')) {
                    $this->data['cert_key'] = file_get_contents($val . DIRECTORY_SEPARATOR . 'apiclient_key.pem');
                }

            }
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }
}
