<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Fields\BasicField;
use Sc\Util\HtmlStructureV2\Components\Fields\CascaderField;
use Sc\Util\HtmlStructureV2\Components\Fields\DateField;
use Sc\Util\HtmlStructureV2\Components\Fields\NumberField;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\PasswordField;
use Sc\Util\HtmlStructureV2\Components\Fields\TextField;
use Sc\Util\HtmlStructureV2\Components\Fields\UploadField;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class Fields
{
    public static function text(string $name, ?string $label = null): TextField
    {
        return new TextField($name, $label ?: $name, FieldType::TEXT);
    }

    public static function password(string $name, ?string $label = null): PasswordField
    {
        return (new PasswordField($name, $label ?: $name))
            ->showPassword();
    }

    public static function textarea(string $name, ?string $label = null): TextField
    {
        return (new TextField($name, $label ?: $name, FieldType::TEXTAREA))
            ->prop('rows', 4);
    }

    public static function number(string $name, ?string $label = null): NumberField
    {
        return new NumberField($name, $label ?: $name);
    }

    public static function select(string $name, ?string $label = null): OptionField
    {
        return new OptionField($name, $label ?: $name, FieldType::SELECT);
    }

    public static function radio(string $name, ?string $label = null): OptionField
    {
        return new OptionField($name, $label ?: $name, FieldType::RADIO);
    }

    public static function checkbox(string $name, ?string $label = null): OptionField
    {
        return (new OptionField($name, $label ?: $name, FieldType::CHECKBOX))
            ->default([]);
    }

    public static function cascader(string $name, ?string $label = null): CascaderField
    {
        return new CascaderField($name, $label ?: $name);
    }

    public static function upload(string $name, ?string $label = null): UploadField
    {
        return new UploadField($name, $label ?: $name);
    }

    public static function image(string $name, ?string $label = null, bool $multiple = false): UploadField
    {
        return (new UploadField($name, $label ?: $name))
            ->asImage($multiple);
    }

    public static function date(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?: $name, FieldType::DATE))
            ->format('YYYY-MM-DD')
            ->valueFormat('YYYY-MM-DD');
    }

    public static function datetime(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?: $name, FieldType::DATETIME))
            ->format('YYYY-MM-DD HH:mm:ss')
            ->valueFormat('YYYY-MM-DD HH:mm:ss');
    }

    public static function daterange(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?: $name, FieldType::DATE_RANGE))
            ->format('YYYY-MM-DD')
            ->valueFormat('YYYY-MM-DD')
            ->prop('range-separator', '至')
            ->prop('start-placeholder', '开始日期')
            ->prop('end-placeholder', '结束日期');
    }

    public static function toggle(string $name, ?string $label = null): BasicField
    {
        return new BasicField($name, $label ?: $name, FieldType::SWITCH);
    }

    public static function hidden(string $name): BasicField
    {
        return new BasicField($name, $name, FieldType::HIDDEN);
    }
}
