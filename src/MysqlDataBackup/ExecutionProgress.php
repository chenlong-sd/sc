<?php
/**
 * datetime: 2023/6/14 1:09
 **/

namespace Sc\Util\MysqlDataBackup;

use Hyperf\Redis\Redis;

class ExecutionProgress
{
    const PROGRESS_KEY = 'back_progress';
    const SIGNAL_KEY = 'back_signal';

    const SIGNAL_BREAK = 'Break';

    const SIGNAL_PROCESS = 'Process';


    /**
     * @param Redis|\Redis $redis
     */
    public function __construct(private mixed $redis)
    {
    }

    public function get(): array
    {
        $messages = [];
        $seek     = 0;
        $code     = 200;
        $msg      = 'success';
        $type     = 'back_up';
        if (!$this->isProcess()) {
            $messages = ["当前无备份信息"];
            $code = 202;
            $type = 'wait';
        }else{
            while($message = $this->redis->rPop(self::PROGRESS_KEY)){
                $messages[] = $message;
            }
            if (array_intersect($messages, ['END', '中断操作'])) {
                $this->clear();
            }
        }

        return compact('messages', 'seek', 'code', 'msg', 'type');
    }

    public function write(string $message): void
    {
        if ($this->isBreak()) {
            throw new \Exception('已中断操作');
        }
        $this->redis->lPush(self::PROGRESS_KEY, $message);
    }

    public function break(): void
    {
        if (!$this->isBreak() && $this->isProcess()) {
            $this->redis->setnx(self::SIGNAL_KEY, self::SIGNAL_BREAK);
            $this->redis->lPush(self::PROGRESS_KEY, "中断操作");
        }
    }

    public function start(): void
    {
        if (!$this->isBreak() && !$this->isProcess()) {
            $this->redis->setnx(self::SIGNAL_KEY, self::SIGNAL_PROCESS);
            return;
        }
        throw new \Exception('已有备份任务正在进行');
    }

    public function clear(): void
    {
        $this->redis->del(self::SIGNAL_KEY);
        $this->redis->del(self::PROGRESS_KEY);
    }

    public function isBreak(): bool
    {
        return $this->redis->get(self::SIGNAL_KEY) === self::SIGNAL_BREAK;
    }

    public function isProcess(): bool
    {
        return $this->redis->get(self::SIGNAL_KEY) === self::SIGNAL_PROCESS;
    }
}