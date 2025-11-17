<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Sc\Util\SecurityComponent\SecurityConfig;
use Sc\Util\SecurityComponent\SecurityHelper;

class RSAEncryption {
    private $privateKey;
    private $publicKey;

    /**
     * 生成RSA密钥对
     */
    public function generateKeyPair() {
        $keyPair = openssl_pkey_new(SecurityConfig::$RSA);

        // 生成私钥
        openssl_pkey_export($keyPair, $privateKey);

        // 获取公钥
        $publicKey = openssl_pkey_get_details($keyPair)["key"];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }

    /**
     * 设置密钥
     */
    public function setKeys($privateKey = null, $publicKey = null) {
        if ($privateKey) {
            $this->privateKey = openssl_pkey_get_private($privateKey);
            if (!$this->privateKey) {
                throw new \Exception('无效的私钥');
            }
        }

        if ($publicKey) {
            $this->publicKey = openssl_pkey_get_public($publicKey);
            if (!$this->publicKey) {
                throw new \Exception('无效的公钥');
            }
        }
    }

    /**
     * RSA加密
     */
    public function encrypt($data, $publicKey = null) {
        $key = $publicKey ? openssl_pkey_get_public($publicKey) : $this->publicKey;

        if (!$key) {
            throw new \Exception('公钥未设置');
        }

        $encrypted = '';
        if (!openssl_public_encrypt($data, $encrypted, $key)) {
            throw new \Exception('RSA加密失败: ' . openssl_error_string());
        }

        return SecurityHelper::arrayToBase64($encrypted);
    }

    /**
     * RSA解密
     */
    public function decrypt($encryptedData, $privateKey = null) {
        $key = $privateKey ? openssl_pkey_get_private($privateKey) : $this->privateKey;

        if (!$key) {
            throw new \Exception('私钥未设置');
        }

        $decrypted = '';
        $data = SecurityHelper::base64ToArray($encryptedData);

        if (!openssl_private_decrypt($data, $decrypted, $key)) {
            throw new \Exception('RSA解密失败: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * 获取公钥
     */
    public function getPublicKey() {
        if (!$this->publicKey) {
            throw new \Exception('公钥未设置');
        }

        $details = openssl_pkey_get_details($this->publicKey);
        return $details['key'];
    }

    /**
     * 数据签名
     */
    public function sign($data, $privateKey = null) {
        $key = $privateKey ? openssl_pkey_get_private($privateKey) : $this->privateKey;

        if (!$key) {
            throw new \Exception('私钥未设置');
        }

        $signature = '';
        if (!openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \Exception('签名失败: ' . openssl_error_string());
        }

        return SecurityHelper::arrayToBase64($signature);
    }

    /**
     * 验证签名
     */
    public function verify($data, $signature, $publicKey = null) {
        $key = $publicKey ? openssl_pkey_get_public($publicKey) : $this->publicKey;

        if (!$key) {
            throw new \Exception('公钥未设置');
        }

        $result = openssl_verify(
            $data,
            SecurityHelper::base64ToArray($signature),
            $key,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }
}
