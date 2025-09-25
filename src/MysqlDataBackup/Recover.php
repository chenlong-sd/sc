<?php
/**
 * datetime: 2023/6/18 1:10
 **/

namespace Sc\Util\MysqlDataBackup;

use Sc\Util\ScTool;
use Sc\Util\Tool;

class Recover
{
    public function __construct(private readonly Connect $connect, private readonly string $filePath = '', private readonly ?ExecutionProgress $progress = null) { }

    /**
     * 恢复数据
     *
     * @return array|int[]
     * @throws \Exception
     */
    public function recover(): array
    {
        $startTime = microtime(true);
        try {
            $this->progress->start();
            $this->progress->write('recover', );

            $splFileObject = new \SplFileObject($this->filePath);

            $size = $splFileObject->getSize();
            $recoverSize = 0;
            $currentSql = '';
            while ($content =  $splFileObject->fgets()) {
                $currentSql .= $content;

                if (str_ends_with($content, ';' . PHP_EOL) || str_ends_with($content, ";\r\n") ) {
                    // 执行sql
                    $this->connect->getPDO()->prepare($currentSql)->execute();

                    $this->progress->write(
                        sprintf("已恢复：%d/%d 已耗时[%f]", $recoverSize += strlen($currentSql), $size, microtime(true) - $startTime),
                        );

                    $currentSql = '';
                }
            }
        } catch (\Throwable $exception) {
            $this->progress->write("ERROR:" . $exception->getMessage(), );
            $result = ['code' => 202, 'msg' => $exception->getMessage()];
        } finally {
            $this->progress->write('总耗时：' . (microtime(true) - $startTime), );
            $this->progress->write('END', );
        }

        return $result ?? ['code' => 200];
    }

    /**
     * 获取可恢复额文件
     *
     * @param $saveDir
     *
     * @return array
     * @throws \Exception
     */
    public function getRecoverFile($saveDir): array
    {
        $backUpData = [];
        if (is_dir($saveDir)) {
            ScTool::dir($saveDir)->each(function (Tool\Dir\EachFile $file) use (&$backUpData){
                if (!str_ends_with($file->filename, '.zip') && !str_ends_with($file->filename, '.sql')) {
                    return;
                }
                $des = $this->getFileDes($file->filepath);

                // 获取文件大小
                $filesize = filesize($file->filepath);

                $backUpData[] = [
                    'filemtime' => filemtime($file->filepath),
                    'filename' => $file->filename,
                    'filesize' => bcdiv($filesize, 1024 * 1024, 2)  . ' MB',
                    'des'      => $des
                ];
            });
            usort($backUpData, function ($a, $b) {
                return  $b['filemtime'] <=> $a['filemtime'];
            });
        }

        return ['code' => 200, 'data' => $backUpData];
    }

    /**
     * 获取备份文件描述
     *
     * @param string $filepath
     * @return array
     */
    private function getFileDes(string $filepath): array
    {
        $des = [];
        if (str_ends_with($filepath, '.zip')) {
            $filepath = strtr($filepath, ['.zip' => '.txt']);
        }
        if (!file_exists($filepath)) {
            return $des;
        }

        $file = new \SplFileObject($filepath);
        while ($file->valid()) {
            $line = $file->fgets();
            if (str_contains($line, '*/')) {
                break;
            }

            $des[] = $line;
        }

        return $des;
    }
}