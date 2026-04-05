<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

final class RuntimeBundleCatalog
{
    /**
     * @return array<int, string>
     */
    public static function shared(): array
    {
        return [
            'runtime-helpers.js',
            'column-display-factory.js',
            'table-runtime-factory.js',
            'request-action-factory.js',
            'form-runtime-factory.js',
            'managed-dialog-factory.js',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function list(): array
    {
        return [
            'list/form-methods.js',
            'list/filter-methods.js',
            'list/table-methods.js',
            'list/dialog-methods.js',
            'list-runtime.js',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function simple(): array
    {
        return [
            'simple/form-methods.js',
            'simple/dialog-methods.js',
            'simple/table-methods.js',
            'simple-runtime.js',
        ];
    }
}
