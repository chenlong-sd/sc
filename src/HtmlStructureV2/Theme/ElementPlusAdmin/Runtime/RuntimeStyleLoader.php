<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

final class RuntimeStyleLoader
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function load(string $filename): string
    {
        $path = __DIR__ . '/styles/' . ltrim($filename, '/');

        if (!isset(self::$cache[$path])) {
            $contents = @file_get_contents($path);
            if ($contents === false) {
                throw new \RuntimeException(sprintf('Unable to load runtime style: %s', $path));
            }

            self::$cache[$path] = $contents;
        }

        return self::$cache[$path];
    }
}
