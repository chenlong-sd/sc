<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Page\AdminPage;
use Sc\Util\HtmlStructureV2\Page\CrudPage;

final class Pages
{
    public static function page(string $title, ?string $key = null): AdminPage
    {
        return AdminPage::make($title, $key);
    }

    public static function crud(string $title, ?string $key = null): CrudPage
    {
        return CrudPage::make($title, $key);
    }
}
