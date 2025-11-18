<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Psr\SimpleCache\CacheInterface;
use Sc\Util\SecurityComponent\Challenge\ChallengeManager;
use Sc\Util\SecurityComponent\SecurityHelper;

/**
 * 统一加密服务
 */
class UnifiedEncryptionService {
    /**
     * @var KeyManagerInterface 密钥管理器
     */
    private KeyManagerInterface $keyManager;

    /**
     * @var ChallengeManager 挑战管理器
     */
    public readonly ChallengeManager $challengeManager;

    /**
     * @param KeyManagerInterface $keyManager 秘钥管理器
     * @param CacheInterface $cache 缓存器
     */
    public function __construct(KeyManagerInterface $keyManager, CacheInterface $cache) {
        $this->keyManager = $keyManager;
        if ($this->keyManager instanceof DynamicKeyManager){
            $this->keyManager->setCache($cache);
        }

        $this->challengeManager = new ChallengeManager($cache);
    }

    /**
     * 设置密钥管理器
     */
    public function setKeyManager(KeyManagerInterface $keyManager): void
    {
        $this->keyManager = $keyManager;
    }

    /**
     * 获取当前密钥管理器类型
     */
    public function getKeyManagerType(): string
    {
        return get_class($this->keyManager);
    }


    public function getPublicKey(string $keyId): bool|string
    {
        return $this->keyManager->getPublicKey($keyId);
    }

    public function getPrivateKey(string $keyId, $passphrase = null): bool|string
    {
        return $this->keyManager->getPrivateKey($keyId, $passphrase);
    }

    /**
     * RSA加密
     * @throws \Exception
     */
    public function rsaEncrypt(string $data, string $keyId, $publicKey = null): string
    {
        // 如果提供了公钥，直接使用
        if ($publicKey) {
            $encrypted = '';
            if (!openssl_public_encrypt($data, $encrypted, $publicKey)) {
                throw new \Exception('RSA加密失败: ' . openssl_error_string());
            }
            return SecurityHelper::base64Encode($encrypted);
        }

        // 使用密钥管理器获取公钥
        $publicKey = $this->getPublicKey($keyId);
        if (!$publicKey) {
            throw new \Exception('公钥获取失败');
        }

        $encrypted = '';
        if (!openssl_public_encrypt($data, $encrypted, $publicKey)) {
            throw new \Exception('RSA加密失败: ' . openssl_error_string());
        }

        return SecurityHelper::base64Encode($encrypted);
    }

    /**
     * RSA解密
     * @throws \Exception
     */
    public function rsaDecrypt(string $encryptedData, string $keyId, string $privateKey = null, string $passphrase = null): string
    {
        // 如果提供了私钥，直接使用
        if ($privateKey) {
            $decrypted = '';
            $data = SecurityHelper::base64Decode($encryptedData);

            if (!openssl_private_decrypt($data, $decrypted, $privateKey)) {
                throw new \Exception('RSA解密失败: ' . openssl_error_string());
            }

            return $decrypted;
        }

        // 使用密钥管理器获取私钥
        $privateKey = $this->getPrivateKey($keyId, $passphrase);
        if (!$privateKey) {
            throw new \Exception('私钥获取失败');
        }

        $decrypted = '';
        $data = SecurityHelper::base64Decode($encryptedData);

        if (!openssl_private_decrypt($data, $decrypted, $privateKey)) {
            throw new \Exception('RSA解密失败: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * 混合加密
     *
     * @param string $data 要加密的数据
     * @param string $keyId
     * @param string|null $publicKey 公钥
     * @return array
     * @throws \Exception
     */
    public function hybridEncrypt(string $data, string $keyId, string $publicKey = null): array
    {
        $aes = new AESEncryption();

        // 生成AES密钥和IV
        $aesKey = $aes->generateKey();
        $iv = $aes->generateIV();

        // 使用AES加密数据
        $aesResult = $aes->encrypt($data, $aesKey, $iv);

        // 使用RSA加密AES密钥
        $encryptedKey = $this->rsaEncrypt($aesKey, $keyId, $publicKey);

        return [
            'encrypted_data' => $aesResult['ciphertext'],
            'encrypted_key' => $encryptedKey,
            'iv' => $aesResult['iv'],
            'key_id' => $keyId
        ];
    }

    /**
     * 混合解密
     *
     * @param string $encryptedData 加密数据
     * @param string $encryptedKey 密钥
     * @param string|null $keyId 密钥ID
     * @param string $iv 初始向量
     * @param string|null $privateKey 私钥
     * @param string|null $passphrase 密码
     * @return string
     * @throws \Exception
     */
    public function hybridDecrypt(string $encryptedData, string $encryptedKey, string $iv, string $keyId, string $privateKey = null, string $passphrase = null): string
    {
        $aes = new AESEncryption();

        // 使用RSA解密AES密钥
        $aesKey = $this->rsaDecrypt($encryptedKey, $keyId, $privateKey, $passphrase);
        // 使用AES解密数据
        $decryptedData = $aes->decrypt($encryptedData, $aesKey, $iv);

        return $decryptedData;
    }

    /**
     * 数据签名
     *
     * @param string $data 要签名的数据
     * @param string $keyId 密钥ID
     * @param string|null $privateKey 私钥
     * @param string|null $passphrase 密码
     * @return string
     * @throws \Exception
     */
    public function sign(string $data, string $keyId, string $privateKey = null, string $passphrase = null): string
    {
        // 如果提供了私钥，直接使用
        if ($privateKey) {
            $signature = '';
            if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                throw new \Exception('签名失败: ' . openssl_error_string());
            }
            return SecurityHelper::base64Encode($signature);
        }

        // 使用密钥管理器获取私钥
        $privateKey = $this->getPrivateKey($keyId, $passphrase);
        if (!$privateKey) {
            throw new \Exception('私钥获取失败');
        }

        $signature = '';
        if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \Exception('签名失败: ' . openssl_error_string());
        }

        return SecurityHelper::base64Encode($signature);
    }

    /**
     * 验证签名
     *
     * @param string $data 要验证的数据
     * @param string $signature 签名
     * @param string $keyId 密钥ID
     * @param string|null $publicKey 公钥
     * @return bool
     * @throws \Exception
     */
    public function verify(string $data, string $signature, string $keyId, string $publicKey = null): bool
    {
        // 如果提供了公钥，直接使用
        if ($publicKey) {
            $result = openssl_verify(
                $data,
                SecurityHelper::base64Decode($signature),
                $publicKey,
                OPENSSL_ALGO_SHA256
            );
            return $result === 1;
        }

        // 使用密钥管理器获取公钥
        $publicKey = $this->getPublicKey($keyId);
        if (!$publicKey) {
            throw new \Exception('公钥获取失败');
        }

        $result = openssl_verify(
            $data,
            SecurityHelper::base64Decode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }

    /**
     * 生成密钥对（委托给密钥管理器）
     * @param string $keyId 密钥ID
     * @param array $options 选项
     * | key_bits    密钥位数
     * | passphrase 密码
     * | expiry     有效期
     * @return array
     */
    public function generateKeyPair(string $keyId,array $options = []): array
    {
        return $this->keyManager->generateKeyPair($keyId, $options);
    }

    /**
     * 验证密钥对（委托给密钥管理器）
     */
    public function validateKeyPair(string $keyId): array
    {
        return $this->keyManager->validateKeyPair($keyId);
    }

    /**
     * 获取密钥信息（委托给密钥管理器）
     */
    public function getKeyInfo(string $keyId): array
    {
        return $this->keyManager->getKeyInfo($keyId);
    }
}
