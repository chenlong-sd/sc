<?php
/**
 * datetime: 2023/5/27 23:43
 **/

namespace Sc\Util\HtmlStructure\Theme;
use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Theme\Interfaces\ResourceThemeInterface;

/**
 * 主题
 *
 * Class Theme
 *
 * @package Sc\Util\HtmlStructure\Theme
 * @date    2023/5/27
 */
class Theme
{
    /**
     * 可用主题
     * 目前主要使用与主题提示
     */
    const AVAILABLE_THEME = ['ElementUI', 'Layui'];

    /**
     * 全局默认主题
     */
    const DEFAULT_THEME = 'ElementUI';

    private static array $renderer = [];

    /**
     * 根据主题获取渲染类
     *
     * @template Render
     *
     * @param string|Render $interfaceClass
     *
     * @param string|null   $theme
     *
     * @return string|Render
     * @date     2023/5/27
     */
    public static function getRenderer(mixed $interfaceClass, #[ExpectedValues(self::AVAILABLE_THEME)] ?string $theme = null): mixed
    {
        if (!$theme) {
            $theme = Html::theme() ?: Theme::DEFAULT_THEME;
        }

        if (empty(self::$renderer[$theme][$interfaceClass])) {
            self::$renderer[$theme][$interfaceClass] = self::makeRenderer($theme, $interfaceClass);
        }

        return self::$renderer[$theme][$interfaceClass];
    }

    /**
     * @param string|null $theme
     * @param string      $interfaceClass
     *
     * @return mixed|string[]
     */
    private static function makeRenderer(?string $theme, string $interfaceClass): mixed
    {
        $themeBaseNamespace = preg_replace('/Theme$/', '', self::class);

        $themeClass = preg_replace('/Interfaces/', $theme, $interfaceClass);
        $themeClass = preg_replace('/Interface$/', '', $themeClass);

        if (class_exists($resource = $themeBaseNamespace . $theme . "\\ResourceTheme")) {
            $resource = new $resource();
            if ($resource instanceof ResourceThemeInterface) {
                $resource->load();
            }
        }

        return new $themeClass();
    }
}