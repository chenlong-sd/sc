<?php
namespace Sc\Util\SecurityComponent;


class SecurityHelper {

    /**
     * 生成密码学安全的随机字符串
     */
    public static function generateRandomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 生成随机字节
     */
    public static function generateRandomBytes(int $length): string
    {
        return random_bytes($length);
    }

    /**
     * 验证时间戳有效性
     */
    public static function validateTimestamp(int $timestamp, $maxAge = 300): bool
    {
        $currentTime = time();
        $requestTime = $timestamp;

        // 允许1分钟时钟偏差
        if ($requestTime > $currentTime + 60) {
            return false;
        }

        // 检查是否过期
        if ($currentTime - $requestTime > $maxAge) {
            return false;
        }

        return true;
    }

    /**
     * 恒定时间比较，防止时序攻击
     */
    public static function hashEquals(string $knownString, string $userString): bool
    {
        return hash_equals($knownString, $userString);
    }

    /**
     * 模拟验证延迟
     */
    public static function simulateDelay(int $minMs = 100, int $maxMs = 500): void
    {
        $delay = rand($minMs, $maxMs);
        usleep($delay * 1000);
    }

    /**
     * 生成客户端标识
     */
    public static function generateClientId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 数组转Base64
     */
    public static function base64Encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Base64转数组
     */
    public static function base64Decode(string $data): bool|string
    {
        return base64_decode($data);
    }
}
