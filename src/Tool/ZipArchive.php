<?php

namespace Sc\Util\Tool;

class ZipArchive
{
    private readonly \ZipArchive $zip;

    private readonly string $originalFile;

    public function __construct(string $originalFile)
    {
        $this->originalFile = $originalFile;

        if (!file_exists($originalFile)) {
            throw new \Exception('Original File does not exist');
        }

        $this->zip = new \ZipArchive();
    }

    /**
     * Extract zip file
     *
     * @param string|null $destinationDir
     * @param bool $isUnlinkOriginalFile
     * @return void
     * @throws \Exception
     */
    public function extract(string $destinationDir = null, bool $isUnlinkOriginalFile = false): void
    {
        $destinationDir = $destinationDir ?? dirname($this->originalFile);
        // 验证文件名后缀
        if (!preg_match('/\.zip$/', $this->originalFile)) {
            throw new \Exception('Original File is not a zip file');
        }
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        $this->zip->open($this->originalFile);
        $this->zip->extractTo($destinationDir);
        $this->zip->close();
        if ($isUnlinkOriginalFile) {
            unlink($this->originalFile);
        }
    }

    /**
     * Create zip file
     *
     * @param string $destinationFile
     * @param bool $isUnlinkOriginalFile
     * @return void
     * @throws \Exception
     */
    public function create(string $destinationFile, bool $isUnlinkOriginalFile = false): void
    {
        // 验证文件名后缀
        if (!preg_match('/\.zip$/', $destinationFile)) {
            $destinationFile .= '.zip';
        }
        if (file_exists($destinationFile)) {
            throw new \Exception('Destination File already exists');
        }
        if (!dirname($destinationFile)) {
            mkdir(dirname($destinationFile), 0755, true);
        }
        $this->zip->open($destinationFile, \ZipArchive::CREATE);
        $this->zip->addFile($this->originalFile, basename($this->originalFile));
        $this->zip->close();
        if ($isUnlinkOriginalFile) {
            unlink($this->originalFile);
        }
    }
}