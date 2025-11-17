<?php
namespace Sc\Util\SecurityComponent;

class SecurityConfig {
    // RSA配置
    public static $RSA = [
        'key_bits' => 4096,
        'digest_alg' => 'sha512',
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    // 挑战值配置
    public static $CHALLENGE = [
        'length' => 32,
        'expiry' => 300, // 5分钟
    ];

    // 密码增强配置
    public static $PASSWORD = [
        'pbkdf2_iterations' => 100000,
        'key_length' => 32,
        'time_window' => 30, // 30秒窗口
    ];

    // AES配置
    public static $AES = [
        'cipher' => 'aes-256-cbc',
        'key_length' => 32,
        'iv_length' => 16,
    ];
}
