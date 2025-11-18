<?php

namespace Sc\Util\SecurityComponent\Crypto;

use Psr\SimpleCache\CacheInterface;

/**
 * 密钥管理接口
 */
interface KeyManagerInterface {
    /**
     * 获取公钥
     */
    public function getPublicKey(string $keyId = null): bool|string;

    /**
     * 获取私钥
     */
    public function getPrivateKey(string $keyId = null, $passphrase = null): bool|string;

    /**
     * 生成密钥对
     */
    public function generateKeyPair(string $keyId = null, array $options = []): array;

    /**
     * 验证密钥有效性
     */
    public function validateKeyPair(string $keyId = null): array;

    /**
     * 删除密钥
     */
    public function deleteKeyPair(string $keyId = null): bool;

    /**
     * 获取密钥信息
     */
    public function getKeyInfo(string $keyId = null): array;
}
