<?php

namespace Sc\Util\HtmlStructureV2;

use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Column;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Fields\BasicField;
use Sc\Util\HtmlStructureV2\Components\Fields\CascaderField;
use Sc\Util\HtmlStructureV2\Components\Fields\DateField;
use Sc\Util\HtmlStructureV2\Components\Fields\NumberField;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\PasswordField;
use Sc\Util\HtmlStructureV2\Components\Fields\TextField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;
use Sc\Util\HtmlStructureV2\Page\AdminPage;
use Sc\Util\HtmlStructureV2\Page\CrudPage;

final class V2
{
    public static function page(string $title, ?string $key = null): AdminPage
    {
        return Pages::page($title, $key);
    }

    public static function crud(string $title, ?string $key = null): CrudPage
    {
        return Pages::crud($title, $key);
    }

    public static function form(string $key): Form
    {
        return Forms::make($key);
    }

    public static function table(string $key): Table
    {
        return Tables::make($key);
    }

    public static function dialog(string $key, string $title): Dialog
    {
        return Dialogs::make($key, $title);
    }

    public static function column(string $label, string $prop): Column
    {
        return Tables::column($label, $prop);
    }

    public static function text(string $name, ?string $label = null): TextField
    {
        return Fields::text($name, $label);
    }

    public static function password(string $name, ?string $label = null): PasswordField
    {
        return Fields::password($name, $label);
    }

    public static function textarea(string $name, ?string $label = null): TextField
    {
        return Fields::textarea($name, $label);
    }

    public static function number(string $name, ?string $label = null): NumberField
    {
        return Fields::number($name, $label);
    }

    public static function select(string $name, ?string $label = null): OptionField
    {
        return Fields::select($name, $label);
    }

    public static function radio(string $name, ?string $label = null): OptionField
    {
        return Fields::radio($name, $label);
    }

    public static function checkbox(string $name, ?string $label = null): OptionField
    {
        return Fields::checkbox($name, $label);
    }

    public static function cascader(string $name, ?string $label = null): CascaderField
    {
        return Fields::cascader($name, $label);
    }

    public static function upload(string $name, ?string $label = null): UploadField
    {
        return Fields::upload($name, $label);
    }

    public static function image(string $name, ?string $label = null, bool $multiple = false): UploadField
    {
        return Fields::image($name, $label, $multiple);
    }

    public static function date(string $name, ?string $label = null): DateField
    {
        return Fields::date($name, $label);
    }

    public static function datetime(string $name, ?string $label = null): DateField
    {
        return Fields::datetime($name, $label);
    }

    public static function daterange(string $name, ?string $label = null): DateField
    {
        return Fields::daterange($name, $label);
    }

    public static function toggle(string $name, ?string $label = null): BasicField
    {
        return Fields::toggle($name, $label);
    }

    public static function hidden(string $name): BasicField
    {
        return Fields::hidden($name);
    }

    public static function action(string $label): Action
    {
        return Actions::make($label);
    }
}
