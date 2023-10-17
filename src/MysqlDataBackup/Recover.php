<?php
/**
 * datetime: 2023/6/18 1:10
 **/

namespace Sc\Util\MysqlDataBackup;

use Sc\Util\Tool;

class Recover
{
    public function __construct(private readonly Connect $connect, private readonly string $filePath = '') { }

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
            ExecutionProgress::writeProgress('recover', $fd);

            $splFileObject = new \SplFileObject($this->filePath);

            $size = $splFileObject->getSize();
            $recoverSize = 0;
            $currentSql = '';
            while ($content =  $splFileObject->fgets()) {
                $currentSql .= $content;
                if (str_ends_with($content, ';' . PHP_EOL)) {
                    // 执行sql
                    $this->connect->getPDO()->prepare($currentSql)->execute();

                    ExecutionProgress::writeProgress(
                        sprintf("已恢复：%d/%d 已耗时[%f]", $recoverSize += strlen($currentSql), $size, microtime(true) - $startTime),
                        $fd);

                    $currentSql = '';
                }
            }
        } catch (\Throwable $exception) {
            ExecutionProgress::writeProgress("ERROR:" . $exception->getMessage(), $fd);
            $result = ['code' => 202, 'msg' => $exception->getMessage()];
        } finally {
            ExecutionProgress::writeProgress('总耗时：' . (microtime(true) - $startTime), $fd);
            ExecutionProgress::writeProgress('END', $fd);
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
            Tool::dir($saveDir)->each(function (Tool\Dir\EachFile $file) use (&$backUpData){
                $handle = fopen($file->filepath, 'r');
                $des = [];
                while ($con = fgets($handle)) {
                    $des[] = $con;
                    if (str_contains($con, '*/')) {
                        break;
                    }
                }
                fseek($handle, 0, SEEK_END);
                $filesize = ftell($handle);
                fclose($handle);
                $filesize = bcmul($filesize, '1', 0);
                $backUpData[] = [
                    'filename' => $file->filename,
                    'filesize' => bcdiv($filesize, 1024 * 1024, 2)  . ' MB',
                    'des'      => $des
                ];
            });
        }

        return ['code' => 200, 'data' => $backUpData];
    }
}