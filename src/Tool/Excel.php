<?php

namespace Sc\Util\Tool;

use Sc\Util\Tool\Excel\Spreadsheet;
use Sc\Util\Tool\Excel\XlsWriter;

class Excel
{
    private const CHARS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    public static function getHandler(array|string $config): XlsWriter|Spreadsheet
    {
        if (class_exists("Vtiful\\Kernel\\Excel")) {
            return new XlsWriter($config);
        }

        return new Spreadsheet($config);
    }

    /**
     * @param int  $columnIndex 列索引值
     *
     * @return string
     */
    public static function columnTag(int $columnIndex): string
    {
        if ($columnIndex <= 25) return self::CHARS[$columnIndex];

        $res = [];
        while ($columnIndex > 0) {
            $res[] = $columnIndex % 26;
            $columnIndex = floor($columnIndex / 26);
        }

        $end = array_shift($res);
        return implode(array_map(fn($v) => self::CHARS[--$v], array_reverse($res))) . self::CHARS[$end];
    }
}