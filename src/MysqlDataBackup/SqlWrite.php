<?php
/**
 * datetime: 2023/6/15 0:18
 **/

namespace Sc\Util\MysqlDataBackup;

use Sc\Util\ScTool;

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
        $this->closeFile();
        @unlink($this->filepath);
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    public function availableZip(): bool
    {
        return class_exists('ZipArchive');
    }

    private function closeFile(): void
    {
        $this->fd and fclose($this->fd);
        $this->fd = null;
    }

    public function toZip(string $des = ""): string
    {
        if (!$this->availableZip()) {
            return "不可压缩";
        }
        $this->closeFile();

        try {
            $zipFilePath = $this->filepath . '.zip';
            ScTool::zip($this->filepath)->create($zipFilePath, true);
            if ($des) {
                file_put_contents($this->filepath . '.txt', $des);
            }
        } catch (\Exception $e) {
            return "压缩失败：" . $e->getMessage();
        }

        return "压缩成功：" . $this->filepath . '.zip';
    }
}