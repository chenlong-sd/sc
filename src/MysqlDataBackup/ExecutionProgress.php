<?php
/**
 * datetime: 2023/6/14 1:09
 **/

namespace Sc\Util\MysqlDataBackup;

class ExecutionProgress
{
    const PROGRESS_FILE = 'ExecutionProgress';
    const END_SIGNAL_FILE = 'ExecutionProgressSignal';

    /**
     * @param int $seek
     *
     * @return array
     * @date 2023/6/14
     */
    public static function getProgress(int $seek = 0): array
    {
        $code     = 200;
        $type     = 'back_up';
        $msg      = "success";
        $isEnd    = false;
        $filePath = __DIR__ . '/tmp/' . self::PROGRESS_FILE;
        $messages = [];

        try {
            if ($fd = fopen($filePath, 'r')) {
                $type = trim(fgets($fd));
                $max  = 1000;
                fseek($fd, $seek);
                while ($message = fgets($fd)) {
                    $messages[] = $message;
                    if ($message === 'END') {
                        $isEnd = true;
                    }
                    if ($max-- < 0){
                        break;
                    }
                }

                $seek = ftell($fd);
            }
        }catch (\Throwable $exception){
            $code = 202;
            $msg  = "当前无备份信息";
        } finally {
            empty($fd) or fclose($fd);
        }

        if ($isEnd) {
            @unlink($filePath);
        }

        return compact('messages', 'seek', 'code', 'msg', 'type');
    }

    /**
     * @param string $message
     * @param null   $fd
     *
     * @throws \Exception
     * @date 2023/6/14
     */
    public static function writeProgress(string $message, &$fd = null): void
    {
        $filename = __DIR__ . "/tmp/" . self::PROGRESS_FILE;
        $signalFilename = __DIR__ . "/tmp/" . self::END_SIGNAL_FILE;
        try {
            if (!is_resource($fd)) {
                if (!is_dir(dirname($filename))){
                    mkdir(dirname($filename));
                }

                if (file_exists($filename)) {
                    throw new \Exception('当前已有备份任务进行中....', 11211);
                }
                $fd = fopen($filename, 'w');
            }

            if ($message !== 'END') {
                $message .= PHP_EOL;
            }

            fwrite($fd, $message);

            if ($message === 'END'){
                fclose($fd);
                sleep(2);
                @unlink($filename);
                @unlink($signalFilename);
            }else if (self::isEnd()){
                fwrite($fd, '<span style="color: #ffa200;font-weight: bold">NOTICE: 取消备份</span>' . PHP_EOL);
                throw new \Exception('取消备份', 11211);
            }
        } catch (\Throwable $exception) {
            @unlink($signalFilename);
            if ($exception->getCode() === 11211) {
                throw $exception;
            }
            $fd and fclose($fd);
            @unlink($filename);
        }
    }

    /**
     * @return void
     */
    public static function end(): void
    {
        if (file_exists($signalFile = __DIR__ . "/tmp/" . self::PROGRESS_FILE)) {

            $splFileObject = new \SplFileObject($signalFile);
            if (trim($splFileObject->fgets()) === 'back_up') {
                file_put_contents( __DIR__ . "/tmp/" . self::END_SIGNAL_FILE, 1);
            }
        }
    }

    /**
     * 是否终止
     *
     * @return bool
     */
    public static function isEnd(): bool
    {
        return file_exists(__DIR__ . "/tmp/" . self::END_SIGNAL_FILE);
    }
}