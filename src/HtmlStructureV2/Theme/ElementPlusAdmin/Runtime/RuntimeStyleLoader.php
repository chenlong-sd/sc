<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

final class RuntimeStyleLoader
{
    /** @var array<string, array{mtime: int, contents: string}> */
    private static array $cache = [];

    public static function load(string $filename): string
    {
        $path = __DIR__ . '/styles/' . ltrim($filename, '/');

        $mtime = @filemtime($path) ?: 0;
        if (!isset(self::$cache[$path]) || self::$cache[$path]['mtime'] !== $mtime) {
            $contents = @file_get_contents($path);
            if ($contents === false) {
                throw new \RuntimeException(sprintf('Unable to load runtime style: %s', $path));
            }

            self::$cache[$path] = [
                'mtime' => $mtime,
                'contents' => $contents,
            ];
        }

        return self::$cache[$path]['contents'];
    }
}
