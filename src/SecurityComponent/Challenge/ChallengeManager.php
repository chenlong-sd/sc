<?php
namespace Sc\Util\SecurityComponent\Challenge;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\SecurityComponent\SecurityConfig;
use Sc\Util\SecurityComponent\SecurityHelper;

/**
 * 挑战管理器
 */
class ChallengeManager {
    private CacheInterface $storage;
    private array $config;

    public function __construct(CacheInterface $storage) {
        $this->storage = $storage;
        $this->config = SecurityConfig::$CHALLENGE;
    }

    /**
     * 创建挑战
     *
     * @param string $clientId 客户端ID
     * @return array
     * @throws InvalidArgumentException
     */
    public function createChallenge(string $clientId): array
    {
        $challengeId = SecurityHelper::generateRandomString(32);
        $challenge = SecurityHelper::generateRandomString($this->config['length']);

        $challengeData = [
            'challenge' => $challenge,
            'client_id' => $clientId,
            'created_at' => time(),
            'used' => false
        ];

        // 存储挑战值
        $this->storage->set($challengeId, $challengeData, $this->config['expiry']);

        return [
            'challenge_id' => $challengeId,
            'challenge' => $challenge,
            'expires_in' => $this->config['expiry']
        ];
    }

    /**
     * 验证挑战值
     *
     * @param string $challengeId 挑战值ID
     * @param string $providedChallenge 提供的挑战值
     * @param string $clientId 客户端ID
     * @return array{valid: bool, reason: string, challenge_data: array}
     * @throws InvalidArgumentException
     */
    public function validateChallenge(string $challengeId, string $providedChallenge, string $clientId): array
    {
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
        $this->storage->delete($challengeId);

        return [
            'valid' => true,
            'challenge_data' => $challengeData
        ];
    }
}
