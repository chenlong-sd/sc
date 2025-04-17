<?php

namespace Sc\Util\Tool;

use Sc\Util\Tool\Locks\LockInterface;

/**
 * Class Lock
 */
class Lock
{
    private LockInterface $lock;
    /**
     * @var callable
     */
    private $callback = null;

    public function __construct(public readonly string $key,
                                public readonly int    $ttl = 5,
                                public readonly int    $waitTime = 0)
    {}

    public function setLock(LockInterface $lock): static
    {
        $this->lock = $lock;
        $this->lock->setLock($this);

        return $this;
    }

    public function fail(callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function locking(callable $callback): mixed
    {
        if (!$this->lock->locking()){
            $this->callback === null ? throw new \Exception('lock fail') : ($this->callback)();
        }

        try {
            $res = $callback();
        } finally{
            $this->lock->unlock();
        }

        return $res;
    }
}