<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

final class RuntimeAssetPublisher
{
    private const PUBLIC_DIR = 'public/js/sc-v2';
    private const PUBLIC_URL = '/js/sc-v2';

    /** @var array<string, string> */
    private static array $urlCache = [];

    public function publishScript(string $filename): ?string
    {
        return $this->publishAsset(
            ltrim(str_replace('\\', '/', $filename), '/'),
            static fn(string $normalized): string => RuntimeScriptLoader::load($normalized)
        );
    }

    public function publishStyle(string $filename): ?string
    {
        return $this->publishAsset(
            ltrim(str_replace('\\', '/', $filename), '/'),
            static fn(string $normalized): string => RuntimeStyleLoader::load($normalized)
        );
    }

    public function publishScriptMany(array $filenames): ?array
    {
        return $this->publishManyUsing($filenames, fn(string $filename): ?string => $this->publishScript($filename));
    }

    /**
     * @param array<int, string> $filenames
     * @return array<int, string>|null
     */
    public function publishMany(array $filenames): ?array
    {
        return $this->publishScriptMany($filenames);
    }

    /**
     * @param array<int, string> $filenames
     * @return array<int, string>|null
     */
    public function publishStyleMany(array $filenames): ?array
    {
        return $this->publishManyUsing($filenames, fn(string $filename): ?string => $this->publishStyle($filename));
    }

    /**
     * @param array<int, string> $filenames
     * @param \Closure(string): ?string $publisher
     * @return array<int, string>|null
     */
    private function publishManyUsing(array $filenames, \Closure $publisher): ?array
    {
        $urls = [];

        foreach ($filenames as $filename) {
            $url = $publisher($filename);
            if ($url === null) {
                return null;
            }

            $urls[] = $url;
        }

        return $urls;
    }

    /**
     * @param \Closure(string): string $loader
     */
    private function publishAsset(string $normalized, \Closure $loader): ?string
    {
        $contents = $loader($normalized);
        $hash = substr(sha1($contents), 0, 12);
        $cacheKey = $normalized . ':' . $hash;

        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

        $targetPath = $this->publicDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $targetDir = dirname($targetPath);
        $url = self::PUBLIC_URL . '/' . $normalized . '?v=' . $hash;

        if (!$this->ensureFileContents($targetDir, $targetPath, $contents)) {
            return null;
        }

        return self::$urlCache[$cacheKey] = $url;
    }

    private function ensureFileContents(string $targetDir, string $targetPath, string $contents): bool
    {
        if (is_file($targetPath) && @file_get_contents($targetPath) === $contents) {
            return true;
        }

        return $this->writeBundle($targetDir, $targetPath, $contents);
    }

    private function writeBundle(string $targetDir, string $targetPath, string $contents): bool
    {
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            $suffix = str_replace('.', '', uniqid('', true));
        }

        $tempPath = sprintf('%s.%s.tmp', $targetPath, $suffix);
        if (@file_put_contents($tempPath, $contents, LOCK_EX) === false) {
            @unlink($tempPath);

            return false;
        }

        @chmod($tempPath, 0644);

        if (@rename($tempPath, $targetPath)) {
            return true;
        }

        @unlink($tempPath);

        return is_file($targetPath);
    }

    private function publicDir(): string
    {
        return $this->projectRoot() . DIRECTORY_SEPARATOR . self::PUBLIC_DIR;
    }

    private function projectRoot(): string
    {
        if (defined('BASE_PATH') && is_string(BASE_PATH) && BASE_PATH !== '') {
            return BASE_PATH;
        }

        return dirname(__DIR__, 6);
    }
}
