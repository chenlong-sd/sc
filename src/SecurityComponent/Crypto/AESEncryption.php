<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Sc\Util\SecurityComponent\SecurityConfig;
use Sc\Util\SecurityComponent\SecurityHelper;

class AESEncryption {

    /**
     * 生成AES密钥
     */
    public function generateKey() {
        return SecurityHelper::generateRandomBytes(SecurityConfig::$AES['key_length']);
    }

    /**
     * 生成IV
     */
    public function generateIV() {
        return SecurityHelper::generateRandomBytes(SecurityConfig::$AES['iv_length']);
    }

    /**
     * AES加密
     */
    public function encrypt($data, $key, $iv = null) {
        if (!$iv) {
            $iv = $this->generateIV();
        }

        $encrypted = openssl_encrypt(
            $data,
            SecurityConfig::$AES['cipher'],
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \Exception('AES加密失败: ' . openssl_error_string());
        }

        return [
            'ciphertext' => SecurityHelper::arrayToBase64($encrypted),
            'iv' => SecurityHelper::arrayToBase64($iv)
        ];
    }

    /**
     * AES解密
     */
    public function decrypt($encryptedData, $key, $iv) {
        $decrypted = openssl_decrypt(
            SecurityHelper::base64ToArray($encryptedData),
            SecurityConfig::$AES['cipher'],
            $key,
            OPENSSL_RAW_DATA,
            SecurityHelper::base64ToArray($iv)
        );

        if ($decrypted === false) {
            throw new \Exception('AES解密失败: ' . openssl_error_string());
        }

        return $decrypted;
    }
}
