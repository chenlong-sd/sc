<?php

namespace Sc\Util\Wechat\Pay\Notice;

use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Pay\Notice\PayNoticeParams\PayNoticeData;

/**
 * Class DataHandle
 */
trait ResolveData
{

    /**
     *
     * @throws \ReflectionException
     */
    public function __construct(protected Config $config, array $data = [])
    {
        foreach (($data ?: $this->getData()) as $key => $value) {
            $this->$key = $value;
        }

        $this->resourceResolve($this->resource);
    }

    /**
     * 获取数据
     *
     * @return mixed
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/1 16:48
     */
    private function getData():mixed
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }

    /**
     * 解析资源
     *
     * @param array $resource
     *
     * @return void
     *
     * @throws \ReflectionException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/1 16:56
     */
    private function resourceResolve(array $resource): void
    {
        $ciphertext     = base64_decode($resource['ciphertext']);
        $nonceStr       = $resource['nonce'];
        $associatedData = $resource['associated_data'];

        $AUTH_TAG_LENGTH_BYTE = 16;

        $cText   = substr($ciphertext, 0, -$AUTH_TAG_LENGTH_BYTE);
        $authTag = substr($ciphertext, -$AUTH_TAG_LENGTH_BYTE);

        $decrypt = openssl_decrypt($cText, 'aes-256-gcm', $this->config->get('v3_key'), \OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
        $decrypt = $decrypt ? json_decode($decrypt, true) : [];

        $this->setData($decrypt);
    }

    /**
     * @param array $data
     *
     * @date 2023/2/15
     */
    private function setData(array $data): void
    {
        try {
            $reflectionClass = new \ReflectionClass($this);
            $name = $reflectionClass->getProperty('data')->getType()->getName();
            $this->data = new $name($data);
        } catch (\ReflectionException $e) {
        }
    }

    /**
     * @return string[]
     */
    public function successReturn(): array
    {
        return [
            'code'    => 'SUCCESS',
            'message' => 'SUCCESS'
        ];
    }

    /**
     * @param string $message
     *
     * @return string[]
     */
    public function failReturn(string $message): array
    {
        return [
            'code'    => 'FAIL',
            'message' => $message
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $property => $value) {
            if (is_array($value)) {
                $tempArray = [];

                foreach ($value as $key => $item) {
                    if (is_object($item)) {
                        $tempArray[$key] = $item->toArray();
                    }else{
                        $tempArray[$key] = $value;
                    }
                }
                $array[$property] = $tempArray;
                continue;
            }

            if (is_object($value)) {
                $array[$property] = $value->toArray();
            }else{
                $array[$property] = $value;
            }
        }

        return $array;
    }
}
