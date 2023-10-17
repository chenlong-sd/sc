<?php

namespace Sc\Util\Wechat;

use Sc\Util\Wechat\Execption\WechatException;

/**
 * Class Request
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/24 13:50
 */
class Request
{
    /**
     * 微信API V3版本的 post 请求
     *
     * @param string $url
     * @param array  $data
     * @param string $sign
     *
     * @return Response
     * @throws WechatException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 13:52
     */
    public static function postV3(string $url, array $data, string $sign): Response
    {
        $header = [
            "User-Agent"    => 'Sc-util(version:1.0)',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => "WECHATPAY2-SHA256-RSA2048  " . $sign
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_map(fn($k, $v) => "$k:$v", array_keys($header), array_values($header)));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        return self::execution($curl);
    }

    /**
     * GET 请求
     *
     * @param string $url
     *
     * @return Response
     *
     * @throws WechatException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 13:55
     */
    public static function get(string $url): Response
    {
        return self::execution(curl_init($url));
    }

    /**
     * POST 请求
     *
     * @param string $url
     * @param array  $data
     *
     * @return Response
     * @throws WechatException
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 14:47
     */
    public static function post(string $url, array $data): Response
    {
        $header = [
            "User-Agent"    => 'Sc-util(version:1.0)',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_map(fn($k, $v) => "$k:$v", array_keys($header), array_values($header)));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        return self::execution($curl);
    }

    /**
     * 执行 curl
     *
     * @param \CurlHandle $curl
     *
     * @return Response
     * @throws WechatException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/24 13:58
     */
    private static function execution(mixed $curl): Response
    {
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        curl_close($curl);

        if (curl_errno($curl)) {
            throw new WechatException(sprintf('%s(code:%d)', curl_error($curl), curl_errno($curl)));
        }

        $result = new Response($result);

        if ($result->isError()) {
            throw new WechatException(sprintf('%s(errcode:%s)', $result->getErrmsg(), $result->getErrcode()));
        }

        return $result;
    }
}
