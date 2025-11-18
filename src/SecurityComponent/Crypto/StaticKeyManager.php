<?php
namespace Sc\Util\SecurityComponent\Crypto;

use Sc\Util\SecurityComponent\SecurityHelper;

/**
 * 静态密钥管理器
 */
class StaticKeyManager implements KeyManagerInterface {
    private string $privateKeyPath;
    private string $publicKeyPath;
    private string $keyId; // 静态密钥的标识

    /**
     * @throws \Exception
     */
    public function __construct(string $privateKeyPath, string $publicKeyPath, string $keyId = 'default') {
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->keyId = $keyId;

        // 如果密钥文件不存在，自动生成
        if (!file_exists($this->privateKeyPath) || !file_exists($this->publicKeyPath)) {
            $this->generateKeyPair();
        }
    }

    /**
     * 获取公钥
     * @throws \Exception
     */
    public function getPublicKey(string $keyId = null): bool|string
    {
        if ($keyId && $keyId !== $this->keyId) {
            throw new \Exception('静态密钥管理器不支持多密钥ID');
        }

        if (!file_exists($this->publicKeyPath)) {
            throw new \Exception('公钥文件不存在: ' . $this->publicKeyPath);
        }

        return file_get_contents($this->publicKeyPath);
    }

    /**
     * 获取私钥
     * @throws \Exception
     */
    public function getPrivateKey(string $keyId = null, $passphrase = null): bool|string
    {
        if ($keyId && $keyId !== $this->keyId) {
            throw new \Exception('静态密钥管理器不支持多密钥ID');
        }

        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception('私钥文件不存在: ' . $this->privateKeyPath);
        }

        $privateKey = file_get_contents($this->privateKeyPath);

        // 验证密码（如果有）
        if ($passphrase) {
            $resource = openssl_pkey_get_private($privateKey, $passphrase);
            if (!$resource) {
                throw new \Exception('私钥密码错误');
            }
        }

        return $privateKey;
    }

    /**
     * 生成密钥对
     * @throws \Exception
     */
    public function generateKeyPair(string $keyId = null, array $options = []): array
    {
        if ($keyId && $keyId !== $this->keyId) {
            throw new \Exception('静态密钥管理器不支持多密钥ID');
        }

        $keyBits = $options['key_bits'] ?? 4096;
        $passphrase = $options['passphrase'] ?? null;

        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => $keyBits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $keyPair = openssl_pkey_new($config);
        if (!$keyPair) {
            throw new \Exception('密钥对生成失败: ' . openssl_error_string());
        }

        // 导出私钥
        $privateKey = '';
        if ($passphrase) {
            openssl_pkey_export($keyPair, $privateKey, $passphrase);
        } else {
            openssl_pkey_export($keyPair, $privateKey);
        }

        // 获取公钥
        $publicKeyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKeyDetails["key"];

        // 确保目录存在
        $privateKeyDir = dirname($this->privateKeyPath);
        $publicKeyDir = dirname($this->publicKeyPath);

        if (!is_dir($privateKeyDir)) {
            mkdir($privateKeyDir, 0700, true);
        }

        if (!is_dir($publicKeyDir)) {
            mkdir($publicKeyDir, 0755, true);
        }

        // 保存私钥
        if (file_put_contents($this->privateKeyPath, $privateKey) === false) {
            throw new \Exception('私钥保存失败');
        }
        chmod($this->privateKeyPath, 0600);

        // 保存公钥
        if (file_put_contents($this->publicKeyPath, $publicKey) === false) {
            unlink($this->privateKeyPath);
            throw new \Exception('公钥保存失败');
        }

        return [
            'key_id' => $this->keyId,
            'public_key' => $publicKey,
            'private_key' => $privateKey
        ];
    }

    /**
     * 验证密钥有效性
     */
    public function validateKeyPair(string $keyId = null): array
    {
        if ($keyId && $keyId !== $this->keyId) {
            return ['valid' => false, 'reason' => '密钥ID不匹配'];
        }

        try {
            $privateKey = $this->getPrivateKey();
            $publicKey = $this->getPublicKey();

            // 测试加密解密
            $testData = "test message";
            $encrypted = '';
            $decrypted = '';

            // 使用公钥加密
            if (!openssl_public_encrypt($testData, $encrypted, $publicKey)) {
                return ['valid' => false, 'reason' => '公钥加密测试失败'];
            }

            // 使用私钥解密
            if (!openssl_private_decrypt($encrypted, $decrypted, $privateKey)) {
                return ['valid' => false, 'reason' => '私钥解密测试失败'];
            }

            if ($decrypted !== $testData) {
                return ['valid' => false, 'reason' => '加解密验证失败'];
            }

            return [
                'valid' => true,
                'key_info' => $this->getKeyInfo()
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * 删除密钥
     * @throws \Exception
     */
    public function deleteKeyPair(string $keyId = null): bool
    {
        if ($keyId && $keyId !== $this->keyId) {
            throw new \Exception('静态密钥管理器不支持多密钥ID');
        }

        $deleted = true;

        if (file_exists($this->privateKeyPath)) {
            $deleted = unlink($this->privateKeyPath);
        }

        if (file_exists($this->publicKeyPath)) {
            $deleted = $deleted && unlink($this->publicKeyPath);
        }

        return $deleted;
    }

    /**
     * 获取密钥信息
     */
    public function getKeyInfo(string $keyId = null): array
    {
        if ($keyId && $keyId !== $this->keyId) {
            throw new \Exception('静态密钥管理器不支持多密钥ID');
        }

        if (!file_exists($this->privateKeyPath)) {
            return ['exists' => false];
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        $publicKey = file_get_contents($this->publicKeyPath);

        $privateKeyDetails = openssl_pkey_get_details(openssl_pkey_get_private($privateKey));
        $publicKeyDetails = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        return [
            'exists' => true,
            'key_id' => $this->keyId,
            'key_type' => 'static',
            'key_bits' => $privateKeyDetails['bits'],
            'private_key_path' => $this->privateKeyPath,
            'public_key_path' => $this->publicKeyPath,
            'created_at' => filemtime($this->privateKeyPath),
            'file_size' => [
                'private_key' => filesize($this->privateKeyPath),
                'public_key' => filesize($this->publicKeyPath)
            ]
        ];
    }

    /**
     * 获取密钥文件路径
     */
    public function getKeyPaths(): array
    {
        return [
            'private_key' => $this->privateKeyPath,
            'public_key' => $this->publicKeyPath
        ];
    }
}
