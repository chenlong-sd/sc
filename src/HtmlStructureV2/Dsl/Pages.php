<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Page\CrudPage;
use Sc\Util\HtmlStructureV2\Page\CustomPage;
use Sc\Util\HtmlStructureV2\Page\FormPage;
use Sc\Util\HtmlStructureV2\Page\ListPage;

final class Pages
{
    /**
     * 创建一个自由组合的自定义页面。
     * 适合完全手工拼装 blocks/layouts/forms/tables/lists/dialogs 的场景。
     */
    public static function custom(string $title, ?string $key = null): CustomPage
    {
        return CustomPage::make($title, $key);
    }

    /**
     * 创建一个以单表单为核心的页面。
     * 会自动注册表单 runtime，适合编辑页、详情页、参数配置页。
     */
    public static function form(string $title, ?string $key = null): FormPage
    {
        return FormPage::make($title, $key);
    }

    /**
     * 创建一个以列表为核心的页面。
     * 适合“筛选 + 表格 + 弹窗”组合场景，列表级事件和刷新能力会自动挂载。
     */
    public static function list(string $title, ?string $key = null): ListPage
    {
        return ListPage::make($title, $key);
    }

    /**
     * 创建一个标准 CRUD 页面，通常包含列表和 editor 弹窗。
     * 适合快速搭建后台增删改查页面，默认约定的编辑弹窗 key 通常为 `editor`。
     */
    public static function crud(string $title, ?string $key = null): CrudPage
    {
        return CrudPage::make($title, $key);
    }
}
