<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Page\CrudPage;
use Sc\Util\HtmlStructureV2\Page\CustomPage;
use Sc\Util\HtmlStructureV2\Page\FormPage;
use Sc\Util\HtmlStructureV2\Page\ListPage;

final class Pages
{
    public static function custom(string $title, ?string $key = null): CustomPage
    {
        return CustomPage::make($title, $key);
    }

    public static function form(string $title, ?string $key = null): FormPage
    {
        return FormPage::make($title, $key);
    }

    public static function list(string $title, ?string $key = null): ListPage
    {
        return ListPage::make($title, $key);
    }

    public static function crud(string $title, ?string $key = null): CrudPage
    {
        return CrudPage::make($title, $key);
    }
}
