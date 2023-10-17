<?php
/**
 * datetime: 2023/6/15 0:18
 **/

namespace Sc\Util\MysqlDataBackup;

class SqlWrite
{
    /**
     * @var false|resource
     */
    private        $fd;
    private readonly string $filepath;

    public function __construct(private readonly string $saveDir)
    {
        $this->filepath = rtrim($this->saveDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('YmdHis') . '.sql';
        if (!is_dir(dirname($this->filepath))) {
            mkdir(dirname($this->filepath));
        }
        $this->fd       = fopen($this->filepath, 'w');
    }

    public function write(string $sql): void
    {
        fwrite($this->fd, $sql . PHP_EOL);
    }

    public function cancel(): void
    {
        $this->fd and fclose($this->fd);
        $this->fd = null;
        @unlink($this->filepath);
    }

    public function __destruct()
    {
        $this->fd and fclose($this->fd);
    }
}