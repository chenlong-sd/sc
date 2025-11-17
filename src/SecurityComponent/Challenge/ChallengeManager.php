<?php
namespace Sc\Util\SecurityComponent\Challenge;

use Sc\Util\SecurityComponent\SecurityConfig;
use Sc\Util\SecurityComponent\SecurityHelper;

interface ChallengeInterface {
    public function store($challengeId, $data, $expiry);
    public function get($challengeId);
    public function delete($challengeId);
    public function markUsed($challengeId);
}

class ChallengeManager {
    private $storage;
    private $config;

    public function __construct(ChallengeInterface $storage) {
        $this->storage = $storage;
        $this->config = SecurityConfig::$CHALLENGE;
    }

    /**
     * 创建登录挑战
     */
    public function createLoginChallenge($username, $clientId) {
        $challengeId = SecurityHelper::generateRandomString(32);
        $challenge = SecurityHelper::generateRandomString($this->config['length']);

        $challengeData = [
            'challenge' => $challenge,
            'username' => $username,
            'client_id' => $clientId,
            'created_at' => time(),
            'used' => false
        ];

        // 存储挑战值
        $this->storage->store($challengeId, $challengeData, $this->config['expiry']);

        return [
            'challenge_id' => $challengeId,
            'challenge' => $challenge,
            'expires_in' => $this->config['expiry']
        ];
    }

    /**
     * 验证挑战值
     */
    public function validateChallenge($challengeId, $providedChallenge, $clientId) {
        $challengeData = $this->storage->get($challengeId);

        if (!$challengeData) {
            return ['valid' => false, 'reason' => '挑战值不存在或已过期'];
        }

        // 检查是否已使用
        if ($challengeData['used']) {
            $this->storage->delete($challengeId);
            return ['valid' => false, 'reason' => '挑战值已被使用'];
        }

        // 检查客户端ID
        if ($challengeData['client_id'] !== $clientId) {
            return ['valid' => false, 'reason' => '客户端不匹配'];
        }

        // 检查挑战值
        if (!SecurityHelper::hashEquals($challengeData['challenge'], $providedChallenge)) {
            return ['valid' => false, 'reason' => '挑战值不匹配'];
        }

        // 检查时效性
        if (time() - $challengeData['created_at'] > $this->config['expiry']) {
            $this->storage->delete($challengeId);
            return ['valid' => false, 'reason' => '挑战值已过期'];
        }

        // 标记为已使用
        $this->storage->markUsed($challengeId);

        return [
            'valid' => true,
            'challenge_data' => $challengeData
        ];
    }
}
