<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class FormPath
{
    public static function normalize(?string $path): string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        return trim($path, '.');
    }

    public static function resolve(?string $prefix, string $path): string
    {
        $prefix = self::normalize($prefix);
        $path = self::normalize($path);

        if ($path === '') {
            return $prefix;
        }

        if (str_starts_with($path, '$.')) {
            return self::normalize(substr($path, 2));
        }

        if ($path === '$') {
            return '';
        }

        if (str_starts_with($path, '$')) {
            return self::normalize(substr($path, 1));
        }

        if ($prefix === '') {
            return $path;
        }

        return $prefix . '.' . $path;
    }

    public static function segments(?string $path): array
    {
        $path = self::normalize($path);
        if ($path === '') {
            return [];
        }

        return array_values(array_filter(
            explode('.', $path),
            static fn(string $segment) => $segment !== ''
        ));
    }

    public static function parent(?string $path): ?string
    {
        $segments = self::segments($path);
        array_pop($segments);

        return $segments === [] ? null : implode('.', $segments);
    }

    public static function get(array $source, ?string $path, mixed $default = null): mixed
    {
        $segments = self::segments($path);
        if ($segments === []) {
            return $source;
        }

        $current = $source;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    public static function set(array &$target, ?string $path, mixed $value): void
    {
        $segments = self::segments($path);
        if ($segments === []) {
            return;
        }

        $current = &$target;
        foreach (array_slice($segments, 0, -1) as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[$segments[count($segments) - 1]] = $value;
    }
}
