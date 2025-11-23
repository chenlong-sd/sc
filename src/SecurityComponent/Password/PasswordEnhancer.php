<?php
namespace Sc\Util\SecurityComponent\Password;

use Sc\Util\SecurityComponent\SecurityConfig;
use Sc\Util\SecurityComponent\SecurityHelper;

class PasswordEnhancer {
    private $config;

    public function __construct() {
        $this->config = SecurityConfig::$PASSWORD;
    }

    /**
     * 计算增强密码
     */
    public function computeEnhancedPassword($password, $challenge, $timestamp = null) {
        if (!$timestamp) {
            $timestamp = time();
        }

        // 时间窗口
        $timeWindow = floor($timestamp / ($this->config['time_window'] * 1000));

        // 使用PBKDF2派生密钥
        $baseKey = $this->pbkdf2(
            $password,
            $challenge,
            $this->config['pbkdf2_iterations'],
            $this->config['key_length']
        );

        // 添加时间因素
        $finalKey = hash_hmac('sha256', (string)$timeWindow, $baseKey, true);

        return SecurityHelper::base64Encode($finalKey);
    }

    /**
     * 验证增强密码
     */
    public function verifyEnhancedPassword($providedPassword, $storedPasswordHash, $challenge, $timestamp) {
        $expectedPassword = $this->computeEnhancedPassword($storedPasswordHash, $challenge, $timestamp);
        return SecurityHelper::hashEquals($expectedPassword, $providedPassword);
    }

    /**
     * PBKDF2实现
     */
    private function pbkdf2($password, $salt, $iterations, $keyLength) {
        $hashLength = 32; // SHA-256哈希长度
        $blockCount = ceil($keyLength / $hashLength);

        $output = '';
        for ($i = 1; $i <= $blockCount; $i++) {
            $last = $salt . pack('N', $i);
            $last = $xorsum = hash_hmac('sha256', $last, $password, true);

            for ($j = 1; $j < $iterations; $j++) {
                $xorsum ^= ($last = hash_hmac('sha256', $last, $password, true));
            }

            $output .= $xorsum;
        }

        return substr($output, 0, $keyLength);
    }
}
