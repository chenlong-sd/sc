<?php
namespace Sc\Util\SecurityComponent\Password;

class PasswordHasher {

    /**
     * 创建密码哈希
     */
    public function hash($password, $options = []) {
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * 验证密码
     */
    public function verify($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * 检查密码是否需要重新哈希
     */
    public function needsRehash($hash, $options = []) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }
}
