<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

final class RuntimeScriptLoader
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function load(string $filename, array $replacements = []): string
    {
        $path = __DIR__ . '/scripts/' . ltrim($filename, '/');

        if (!isset(self::$cache[$path])) {
            $contents = @file_get_contents($path);
            if ($contents === false) {
                throw new \RuntimeException(sprintf('Unable to load runtime script: %s', $path));
            }

            self::$cache[$path] = $contents;
        }

        return $replacements === []
            ? self::$cache[$path]
            : strtr(self::$cache[$path], $replacements);
    }

    /**
     * @param array<int, string> $filenames
     * @param array<string, string> $replacements
     */
    public static function loadMany(array $filenames, array $replacements = []): string
    {
        $contents = implode(PHP_EOL . PHP_EOL, array_map(
            static fn (string $filename): string => self::load($filename),
            $filenames
        ));

        return $replacements === []
            ? $contents
            : strtr($contents, $replacements);
    }
}
