<?php

namespace Sc\Util\Tool\Locks;

use Sc\Util\Tool\Lock;

/**
 * Class FileLock
 */
class FileLock implements LockInterface
{
    private ?Lock $lockInfo;
    /**
     * @var false|resource
     */
    private $fopen = null;

    public function __construct(private readonly string $keyPath)
    {}

    public function setLock(?Lock $lock): void
    {
        $this->lockInfo = $lock;
    }

    public function locking(): bool
    {
        $this->getLock($this->lockInfo->key);

        return flock($this->fopen, LOCK_EX);
    }

    public function unlock(): void
    {
        flock($this->fopen, LOCK_UN);
    }

    /**
     * @param $lockSign
     *
     * @return void
     */
    private function getLock($lockSign): void
    {
        $key = strtr($lockSign, ['/' => "-", '\\' => '-', ':' => '.']);

        if (!is_dir($this->keyPath)) {
            mkdir($this->keyPath, 0777, true);
        }
        $this->fopen = fopen($this->keyPath . '/' . $key, 'a+');
    }
}