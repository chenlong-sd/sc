<?php

namespace Sc\Util\Tool\Locks;

use Sc\Util\Tool\Lock;

/**
 * Class RedisLock
 */
class RedisLock implements LockInterface
{
    private ?Lock $lockInfo = null;
    private string $lockId = '';

    public function __construct(
        /**
         * @var \Redis $redis
         */
        private $redis
    )
    {}

    /**
     * @return bool
     */
    public function locking(): bool
    {
        try {
            $waitTime = $this->lockInfo->waitTime;
            $this->lockId = uniqid("r", true);
            $res = $this->redis->set($this->lockInfo->key, $this->lockId, ['nx', 'ex' => $this->lockInfo->ttl]);

            if (!$res && $waitTime <= 0) {
                return false;
            }

            $waitTime = $waitTime * 1000000;
            $microseconds = 10000;
            while (!$res) {
                $waitTime -= $microseconds;
                usleep($microseconds);

                if ($waitTime <= 0) {
                    return false;
                }

                $res = $this->redis->set($this->lockInfo->key, $this->lockId, ['nx', 'ex' => $this->lockInfo->ttl]);
            }
        } catch (\RedisException $e) {
            $this->unlock();
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws \RedisException
     */
    public function unlock(): void
    {
        $luaScript = <<<LUA
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
            LUA;

        $this->redis->eval($luaScript, [$this->lockInfo->key, $this->lockId], 1);
    }

    public function setLock(?Lock $lock): void
    {
        $this->lockInfo = $lock;
    }
}