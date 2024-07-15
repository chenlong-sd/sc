<?php

namespace Sc\Util\Tool\Locks;

use Sc\Util\Tool\Lock;

/**
 * Class RedisLock
 */
class RedisLock implements LockInterface
{
    private ?Lock $lockInfo = null;
    private float $startTime;

    public function __construct(
        /**
         * @var \Redis $redis
         */
        private $redis
    )
    {}

    /**
     * @throws \RedisException
     */
    public function locking(): bool
    {
        $waitTime = $this->lockInfo->waitTime;
        $res = $this->redis->set($this->lockInfo->key, 1, ['nx', 'ex' => $this->lockInfo->ttl]);

        if (!$res && $waitTime <= 0) {
            return false;
        }

        while (!$res) {
            $microseconds = 10000;
            $waitTime -= $microseconds / 1000000;
            usleep($microseconds);

            if ($waitTime <= 0) {
                return false;
            }

            $res = $this->redis->set($this->lockInfo->key, 1, ['nx', 'ex' => $this->lockInfo->ttl]);
        }

        $this->startTime = microtime(true);

        return true;
    }

    public function unlock(): void
    {
        try {
            if (empty($this->lockInfo->ttl) || microtime(true) - $this->startTime < $this->lockInfo->ttl) {
                $this->redis->del($this->lockInfo->key);
            }
        } catch (\RedisException $e) {
        }
    }

    public function setLock(?Lock $lock): void
    {
        $this->lockInfo = $lock;
    }
}