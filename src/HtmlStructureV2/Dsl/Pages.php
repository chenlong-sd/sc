<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Page\Page;

final class Pages
{
    /**
     * 创建一个页面容器。
     * "$title" 用于 HTML "<title>"；页面展示头部建议继续通过 "->header(...)" 自定义组合。
     * 页面本身保持自由组合，适合手工拼装 blocks/layouts/forms/tables/lists/dialogs。
     */
    public static function make(string $title, ?string $key = null): Page
    {
        return Page::make($title, $key);
    }
}
