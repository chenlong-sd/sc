<?php
/**
 * datetime: 2023/5/27 23:43
 **/

namespace Sc\Util\HtmlStructure\Theme;
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
    public static function getRender(mixed $interfaceClass, ?string $theme = null): mixed
    {
        $theme = $theme === null ? Html::theme() : $theme;

        $themeBaseNamespace = preg_replace('/Theme$/', '', self::class);

        $themeClass = preg_replace('/Interfaces/', $theme, $interfaceClass);
        $themeClass = preg_replace('/Interface$/', '', $themeClass);

        if (class_exists($resource = $themeBaseNamespace . $theme . "\\ResourceTheme")){
            $resource = new $resource();
            if ($resource instanceof ResourceThemeInterface) {
                $resource->load();
            }
        }

        return new $themeClass();
    }
}