<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Sc\Util\SecurityComponent\SecurityHelper;

class HybridEncryption {
    private $rsa;
    private $aes;

    public function __construct() {
        $this->rsa = new RSAEncryption();
        $this->aes = new AESEncryption();
    }

    /**
     * 设置RSA密钥
     */
    public function setRSAKeys($privateKey = null, $publicKey = null) {
        $this->rsa->setKeys($privateKey, $publicKey);
    }

    /**
     * 混合加密
     */
    public function encrypt($data, $publicKey = null) {
        // 生成随机的AES密钥和IV
        $aesKey = $this->aes->generateKey();
        $iv = $this->aes->generateIV();

        // 使用AES加密数据
        $aesResult = $this->aes->encrypt($data, $aesKey, $iv);

        // 使用RSA加密AES密钥
        $encryptedKey = $this->rsa->encrypt($aesKey, $publicKey);

        return [
            'encrypted_data' => $aesResult['ciphertext'],
            'encrypted_key' => $encryptedKey,
            'iv' => $aesResult['iv']
        ];
    }

    /**
     * 混合解密
     */
    public function decrypt($encryptedData, $encryptedKey, $iv, $privateKey = null) {
        // 使用RSA解密AES密钥
        $aesKey = $this->rsa->decrypt($encryptedKey, $privateKey);

        // 使用AES解密数据
        $decryptedData = $this->aes->decrypt($encryptedData, $aesKey, $iv);

        return $decryptedData;
    }

    /**
     * 获取公钥
     */
    public function getPublicKey() {
        return $this->rsa->getPublicKey();
    }
}
