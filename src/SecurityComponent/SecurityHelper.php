<?php
namespace Sc\Util\SecurityComponent;

class SecurityHelper {

    /**
     * 生成密码学安全的随机字符串
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 生成随机字节
     */
    public static function generateRandomBytes($length) {
        return random_bytes($length);
    }

    /**
     * 验证时间戳有效性
     */
    public static function validateTimestamp($timestamp, $maxAge = 300) {
        $currentTime = time();
        $requestTime = intval($timestamp);

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
    public static function hashEquals($knownString, $userString) {
        return hash_equals($knownString, $userString);
    }

    /**
     * 模拟验证延迟
     */
    public static function simulateDelay($minMs = 100, $maxMs = 500) {
        $delay = rand($minMs, $maxMs);
        usleep($delay * 1000);
    }

    /**
     * 生成客户端标识
     */
    public static function generateClientId() {
        return bin2hex(random_bytes(16));
    }

    /**
     * 数组转Base64
     */
    public static function arrayToBase64($data) {
        return base64_encode($data);
    }

    /**
     * Base64转数组
     */
    public static function base64ToArray($data) {
        return base64_decode($data);
    }
}
