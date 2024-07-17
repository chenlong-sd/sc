<?php

namespace Sc\Util\Tool\Locks;

use Sc\Util\Tool\Lock;

/**
 * Interface LockInterface
 */
interface LockInterface
{
    public function setLock(?Lock $lock);

    public function locking(): bool;

    public function unlock();
}