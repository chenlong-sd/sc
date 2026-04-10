<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Page\Page;

final class Pages
{
    /**
     * 创建一个页面容器。
     * "$title" 用于 HTML "<title>"；页面展示头部建议继续通过 "->header(...)" 自定义组合。
     * 页面本身保持自由组合，适合手工拼装 blocks/layouts/forms/tables/lists/dialogs。
     *
     * @param string $title 页面 HTML 标题。
     * @param string|null $key 页面唯一 key；传 null 时会由标题自动推导。
     * @return Page 页面实例。
     *
     * 示例：
     * `Pages::make('问答信息')->header('问答信息管理')`
     */
    public static function make(string $title, ?string $key = null): Page
    {
        return Page::make($title, $key);
    }
}
