<?php
/**
 * datetime: 2023/5/22 1:40
 **/

namespace Sc\Util\HtmlStructure\Html\Js;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;

/**
 * js语法
 *
 * Class Grammar
 *
 * @package Sc\Util\HtmlStructure\Html\Js
 * @date    2023/5/22
 */
class Grammar
{
    // js 语法
    private const MARK_START = "#{#";
    private const MARK_END = "#}#";

    // 包含换行字符串
    private const Line_START = '#{l#';
    private const Line_END = '#l}#';

    /**
     * 标记语法
     *
     * @param string $jsCode
     * @param string $mode
     *
     * @return string
     * @date 2023/5/22
     */
    public static function mark(#[Language("JavaScript")] string $jsCode, #[ExpectedValues(['grammar', 'line'])] string $mode = 'grammar'): string
    {
        if ($mode === 'grammar') {
            return self::MARK_START . $jsCode . self::MARK_END;
        }

        return self::Line_START . $jsCode . self::Line_END;
    }

    /**
     * 提取
     *
     * @param string $jsCode
     *
     * @return string
     * @date 2023/5/22
     */
    public static function extract(string $jsCode): string
    {
        // 这个是处理正则表达式的
        $jsCode = preg_replace_callback('/"' . self::MARK_START . '\\\\\/(.*?)\\\\\/' . self::MARK_END . '"/', function ($match){
            return  '/' . preg_replace('/(?<!\\\)\\\/', '', $match[1]). '/';
        }, $jsCode);

        // 这个是处理函数类型的代码的时候
        $jsCode = preg_replace_callback('/"' . self::MARK_START . '(.*?)' . self::MARK_END . '"/', function ($match){
            return  preg_replace('/\\\(.)/', '$1', strtr($match[1], ['\r\n' => "\r\n", '\n' => "\n"]));
        }, $jsCode);

        return strtr($jsCode, [
            '\\/' => '/',
            '"' . self::MARK_START => '',
            self::MARK_END . '"'   => '',
            self::MARK_START       => '',
            self::MARK_END         => '',
            '"' . self::Line_START => '`',
            self::Line_END . '"'   => '`',
            self::Line_START       => '',
            self::Line_END         => '',
        ]);
    }

    /**
     * 检测是否应该被标记
     *
     * @param $data
     *
     * @return bool
     */
    public static function check($data): bool
    {
        if (is_string($data) && str_starts_with($data, '@') ) {
            return true;
        }
        return false;
    }
}