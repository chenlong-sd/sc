<?php

namespace Sc\Util\Tool;

use Sc\Util\Tool\Dir\EachFile;

/**
 * 遍历文件夹
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/6/28 10:36
 */
class Dir
{
    /**
     * @var array|string[]
     */
    private array $relativelyDir = [];

    /**
     * 优先处理的文件名
     *
     * @var string
     */
    private string $priorityFile = '';

    /**
     * @throws \Exception
     */
    public function __construct(private string $dir)
    {
        if (!is_dir($this->dir)) {
            throw new \Exception('文件夹不存在: ' . $this->dir);
        }
    }

    /**
     * 设置要遍历的相对目录
     *
     * @param array|string $relativelyDir
     *
     * @return $this
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/28 11:07
     */
    public function setRelativelyDir(array|string $relativelyDir): static
    {
        $this->relativelyDir = is_array($relativelyDir) ? $relativelyDir : explode('/', strtr($relativelyDir, ['\\' => '/']));

        return $this;
    }

    /**
     * 删除文件夹
     *
     * @return bool
     */
    public function remove(): bool
    {
        return $this->removeDir($this->dir);
    }

    /**
     * @param $folder
     *
     * @return bool
     */
    private function removeDir($folder): bool
    {
        $files = array_diff(scandir($folder), array('.', '..'));

        foreach ($files as $file) {
            $path = $folder . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        return rmdir($folder);
    }

    /**
     * 复制文件夹到指定路径
     *
     * @param string $toPath 目标路径
     *
     * @return void
     * @throws \Exception
     */
    public function copyTo(string $toPath): void
    {
        $this->traverse(function (EachFile $file) use ($toPath){
            $dir = implode(DIRECTORY_SEPARATOR, [$toPath, ...$file->relativelyDirs]);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            copy($file->filepath, $dir . DIRECTORY_SEPARATOR . $file->filename);
        });
    }

    /**
     * 设置优先处理的文件名
     *
     * @param string $filename
     *
     * @return Dir
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/28 11:39
     */
    public function priorityFile(string $filename): static
    {
        $this->priorityFile = $filename;

        return $this;
    }

    /**
     * 开始遍历循环
     *
     * @param callable $callable
     *  回调函数 ，参数 eachFile {@see EachFile}
     * @return void
     *
     * @throws \Exception
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/28 10:40
     */
    public function each(callable $callable): void
    {
        $this->traverse($callable, $this->relativelyDir);
    }

    /**
     * 遍历目录
     *
     * @param callable $callable      回调
     * @param array    $relativelyDir 相对目录集合
     *
     * @return void
     *
     * @throws \Exception
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/6/28 10:49
     */
    private function traverse(callable $callable, array $relativelyDir = []): void
    {
        $realpath = realpath($this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativelyDir));

        try {
            // 处理优先文件
            $path = $realpath . DIRECTORY_SEPARATOR . $this->priorityFile;
            if ($this->priorityFile && file_exists($path)){
                if (str_contains($this->priorityFile, DIRECTORY_SEPARATOR)) {
                    $priorityFiles = explode(DIRECTORY_SEPARATOR, $this->priorityFile);
                    $eachFile = new EachFile(array_pop($priorityFiles), $path, [...$relativelyDir, ...$priorityFiles]);
                }else{
                    $eachFile = new EachFile($this->priorityFile, $path, $relativelyDir);
                }
                call_user_func($callable, $eachFile);
            }

            $fd = opendir($realpath);
            while ($current = readdir($fd)) {
                if ($current === '.' || $current === '..' || $this->priorityFile === $current) continue;

                $path = $realpath . DIRECTORY_SEPARATOR . $current;
                if (is_dir(realpath($path))) {
                    $this->traverse($callable, [...$relativelyDir, $current]);
                    continue;
                }
                $eachFile = new EachFile($current, $path, $relativelyDir);
                call_user_func($callable, $eachFile);
            }
        }catch (\Exception){} finally {
            empty($fd) or closedir($fd);
        }
    }

    /**
     * 获取子文件夹
     *
     * @return array
     */
    public function getDirs(): array
    {
        return $this->getChildren(true);
    }

    /**
     * 获取子文件
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->getChildren();
    }

    /**
     * @param bool $is_dir
     *
     * @return array
     */
    private function getChildren(bool $is_dir = false): array
    {
        $realpath = realpath($this->dir);
        $children = [];
        try {
            $fd = opendir($realpath);
            while ($current = readdir($fd)) {
                if ($current === '.' || $current === '..') continue;

                $path = $realpath . DIRECTORY_SEPARATOR . $current;
                if ($is_dir && is_dir(realpath($path))) {
                    $children[] = $current;
                }else if (!$is_dir && !is_dir(realpath($path))) {
                    $children[] = $current;
                }
            }
        }finally {
            empty($fd) or closedir($fd);
        }

        return $children;
    }

    /**
     * @param array $relativelyDir
     *
     * @return array
     */
    public function getAllFiles(array $relativelyDir = []): array
    {
        $files = [];

        try {
            $realpath = realpath($this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativelyDir));

            $fd = opendir($realpath);
            while ($current = readdir($fd)) {
                if ($current === '.' || $current === '..') continue;

                $path = $realpath . DIRECTORY_SEPARATOR . $current;
                if (is_dir(realpath($path))) {
                    $files[] = [
                        "filename" => $current,
                        "path"     => $path,
                        "type"     => "dir",
                        "children" => $this->getAllFiles([...$relativelyDir, $current]),
                    ];
                    continue;
                }

                $files[] = [
                    "filename" => $current,
                    "path"     => $path,
                    "type"     => "file",
                ];
            }
        } finally {
            empty($fd) or closedir($fd);
        }

        return $files;
    }
}
