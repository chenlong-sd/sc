<?php
/**
 * datetime: 2023/5/15 0:22
 **/

namespace Sc\Util\HtmlStructure\Html;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Theme\Interfaces\ResourceThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use Swoole\Coroutine;

/**
 * Html 单页面
 *
 * Class Html
 * @method static Js js()
 * @method static Css css()
 * @method static DoubleLabel html()
 * @method static string theme()
 *
 * @package Sc\Util\HtmlStructure\Html
 * @date    2023/5/15
 */
class Html
{
    /**
     * 页面基础代码
     */
    private const BASE_CODE = <<<CODE
    <!DOCTYPE html>
    <html lang="zh-cn">
    <head>
      <meta charset="UTF-8">
      <title></title>
    </head>
    <body><div id="app"></div></body>
    </html>
    CODE;

    /**
     * 整个HTML代码
     *
     * @var AbstractHtmlElement
     */
    public AbstractHtmlElement $html;

    /**
     * 页面js
     * @var Js
     */
    public Js $js;

    /**
     * 页面css
     * @var Css
     */
    public Css $css;

    /**
     * 显示主题
     *
     * @var string
     */
    public readonly string $theme;

    private function __construct(string $title, #[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = Theme::DEFAULT_THEME)
    {
        $this->html = El::fromCode(self::BASE_CODE);
        $this->html->find('title')->append($title);

        $this->js  = new Js();
        $this->css = new Css();
        $this->css->addCss('html,body{height: 100%}body{margin: 0 8px;padding-top: 8px;box-sizing: border-box;}');

        $this->theme = $theme;
    }

    /**
     * 创建Html页面
     *
     * @param string $title
     * @param string $theme
     *
     * @return Html
     * @date 2023/5/15
     */
    public static function create(string $title, #[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = Theme::DEFAULT_THEME): Html
    {
        $html = new self($title, $theme);

        self::cacheHtmlGlobal($html);

        return $html;
    }

    /**
     * 手动加载资源
     *
     * @param string|null $theme
     *
     * @date 2023/6/28
     */
    public static function loadThemeResource(#[ExpectedValues(Theme::AVAILABLE_THEME)] ?string $theme = null): void
    {
        Theme::getRenderer(ResourceThemeInterface::class, $theme)->load();
    }

    /**
     * 转为html代码
     *
     * @return string
     * @date 2023/5/15
     */
    public static function toHtml(): string
    {
        $css = self::css()->toCode();
        $js  = self::js()->toCode();

        $css and self::html()->find('head')->append(El::double('style')->append(El::text($css)));
        $js  and self::html()->find('html')->append(El::double('script')->append(El::text($js)));

        self::html()->find('html')->eachChildren(function (AbstractHtmlElement $element) {
            $element->setRetraction(0);
        });

        return trim(El::fictitious()->append(El::text('<!DOCTYPE html>'), self::html())->toHtml());
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return self::getGlobalHtml()->{$name};
    }

    /**
     * swoole环境检测
     *
     * @return bool
     * @date 2023/5/15
     */
    private static function isRunSwoole(): bool
    {
        if (class_exists('\\Swoole\\Coroutine') && Coroutine::getCid() > 0) {
            return true;
        }

        return false;
    }

    /**
     * 缓存全局Html
     *
     * @param Html $html
     *
     * @date 2023/5/15
     */
    private static function cacheHtmlGlobal(Html $html): void
    {
        if (self::getGlobalHtml()) {
            return;
        }

        if (self::isRunSwoole()){
            $context = Coroutine::getContext();
            $context['SC__HTML__SC'] = $html;
            return;
        }

        global $globalHtml;

        $globalHtml = $html;
    }

    /**
     * 获取全局Html
     *
     * @return mixed|Html
     * @date 2023/5/15
     */
    private static function getGlobalHtml(): mixed
    {
        if (self::isRunSwoole()){
            $context = Coroutine::getContext();
            return $context['SC__HTML__SC'] ?? null;
        }

        global $globalHtml;

        return $globalHtml;
    }
}