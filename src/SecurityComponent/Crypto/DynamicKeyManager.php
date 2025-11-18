<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\SecurityComponent\SecurityHelper;

/**
 * 动密钥管理器
 */
class DynamicKeyManager implements KeyManagerInterface {
    private ?CacheInterface $cache;
    private string $keyPrefix;
    private int $defaultExpiry;

    public function __construct(CacheInterface $cache = null, string $keyPrefix = 'sec:component:', int $defaultExpiry = 300) {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
        $this->defaultExpiry = $defaultExpiry;
    }

    /**
     * 获取公钥
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function getPublicKey(string $keyId = null): bool|string
    {
        $this->keyIdCheck($keyId);

        $keyPair = $this->getKeyPair($keyId);
        return $keyPair ? $keyPair['public_key'] : "";
    }

    /**
     * 获取私钥
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function getPrivateKey(string $keyId = null, $passphrase = null): bool|string
    {
        $this->keyIdCheck($keyId);

        $keyPair = $this->getKeyPair($keyId);
        if (!$keyPair) {
            return "";
        }

        // 动态密钥暂不支持密码保护
        if ($passphrase) {
            throw new \Exception('动态密钥暂不支持密码保护');
        }

        return $keyPair['private_key'];
    }

    /**
     * 生成密钥对
     * @throws \Exception|InvalidArgumentException
     */
    public function generateKeyPair($keyId = null, array $options = []): array
    {
        $keyId = $keyId ?: SecurityHelper::generateRandomString(32);
        $expiry = $options['expiry'] ?? $this->defaultExpiry;
        $keyBits = $options['key_bits'] ?? 2048;

        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => $keyBits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $keyPair = openssl_pkey_new($config);
        if (!$keyPair) {
            throw new \Exception('临时密钥对生成失败: ' . openssl_error_string());
        }

        // 导出私钥
        $privateKey = '';
        openssl_pkey_export($keyPair, $privateKey);

        // 获取公钥
        $publicKeyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKeyDetails["key"];

        // 存储到Redis
        $keyData = [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
            'created_at' => time(),
            'expires_at' => time() + $expiry,
            'key_bits' => $keyBits
        ];

        $key = $this->keyPrefix . $keyId;
        $result = $this->cache->set($key, serialize($keyData), $expiry);

        if (!$result) {
            throw new \Exception('临时密钥存储到Redis失败');
        }

        return [
            'key_id' => $keyId,
            'public_key' => $publicKey,
            'expires_in' => $expiry
        ];
    }

    /**
     * 验证密钥有效性
     * @param string|null $keyId
     * @return array
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function validateKeyPair(string $keyId = null): array
    {
        $this->keyIdCheck($keyId);

        $keyPair = $this->getKeyPair($keyId);

        if (!$keyPair) {
            return ['valid' => false, 'reason' => '密钥不存在'];
        }

        if (time() > $keyPair['expires_at']) {
            $this->deleteKeyPair($keyId);
            return ['valid' => false, 'reason' => '密钥已过期'];
        }

        return [
            'valid' => true,
            'key_info' => $this->getKeyInfo($keyId)
        ];
    }

    /**
     * 删除密钥
     * @throws \Exception|InvalidArgumentException
     */
    public function deleteKeyPair(string $keyId = null): bool
    {
        $this->keyIdCheck($keyId);

        $key = $this->keyPrefix . $keyId;
        return $this->cache->delete($key);
    }

    /**
     * 获取密钥信息
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function getKeyInfo(string $keyId = null): array
    {
        $this->keyIdCheck($keyId);

        $keyPair = $this->getKeyPair($keyId);

        if (!$keyPair) {
            return ['exists' => false];
        }

        $timeLeft = $keyPair['expires_at'] - time();

        return [
            'exists' => true,
            'key_id' => $keyId,
            'key_type' => 'dynamic',
            'key_bits' => $keyPair['key_bits'] ?? 2048,
            'created_at' => $keyPair['created_at'],
            'expires_at' => $keyPair['expires_at'],
            'time_left' => max($timeLeft, 0),
            'is_expired' => $timeLeft <= 0
        ];
    }

    /**
     * 获取密钥对
     * @throws InvalidArgumentException
     */
    private function getKeyPair($keyId) {
        $redisKey = $this->keyPrefix . $keyId;
        $keyData = $this->cache->get($redisKey);

        if (!$keyData) {
            return null;
        }

        return unserialize($keyData);
    }

    /**
     * @param $keyId
     * @return void
     * @throws \Exception
     */
    public function keyIdCheck($keyId): void
    {
        if (!$keyId) {
            throw new \Exception('动态密钥管理器需要keyId参数');
        }
    }

    public function setCache(CacheInterface $cache): static
    {
        $this->cache = $cache;
        return $this;
    }
}
