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
    /**
     * 创建单行文本输入框。
     * label 不传时默认隐藏字段标签；若仍需可读提示，建议显式补 placeholder()。
     */
    public static function text(string $name, ?string $label = null): TextField
    {
        return new TextField($name, $label ?? '', FieldType::TEXT);
    }

    /**
     * 创建密码输入框，并默认开启显示/隐藏密码能力。
     */
    public static function password(string $name, ?string $label = null): PasswordField
    {
        return (new PasswordField($name, $label ?? ''))
            ->showPassword();
    }

    /**
     * 创建多行文本输入框，默认 4 行。
     */
    public static function textarea(string $name, ?string $label = null): TextField
    {
        return (new TextField($name, $label ?? '', FieldType::TEXTAREA))
            ->prop('rows', 4);
    }

    /**
     * 创建数字输入框。
     */
    public static function number(string $name, ?string $label = null): NumberField
    {
        return new NumberField($name, $label ?? '');
    }

    /**
     * 创建下拉选择框。
     * 后续可继续链式配置 options()/remoteOptions()/linkageUpdate() 等选项运行时行为。
     */
    public static function select(string $name, ?string $label = null): OptionField
    {
        return new OptionField($name, $label ?? '', FieldType::SELECT);
    }

    /**
     * 创建单选组。
     * 与 select() 共用同一套选项/远端加载/联动能力，只是展示形态不同。
     */
    public static function radio(string $name, ?string $label = null): OptionField
    {
        return new OptionField($name, $label ?? '', FieldType::RADIO);
    }

    /**
     * 创建多选组，并默认值设为空数组。
     * 适合多值枚举；默认模型值会初始化为 `[]`。
     */
    public static function checkbox(string $name, ?string $label = null): OptionField
    {
        return (new OptionField($name, $label ?? '', FieldType::CHECKBOX))
            ->default([]);
    }

    /**
     * 创建级联选择器。
     */
    public static function cascader(string $name, ?string $label = null): CascaderField
    {
        return new CascaderField($name, $label ?? '');
    }

    /**
     * 创建通用上传字段。
     * 后续通常至少继续链式配置 uploadUrl()；返回值默认按单文件字符串处理。
     */
    public static function upload(string $name, ?string $label = null): UploadField
    {
        return new UploadField($name, $label ?? '');
    }

    /**
     * 创建图片上传字段，可通过 $multiple 控制是否多图。
     * 内部会自动切到图片模式和 `picture-card` 列表样式。
     */
    public static function image(string $name, ?string $label = null, bool $multiple = false): UploadField
    {
        return (new UploadField($name, $label ?? ''))
            ->asImage($multiple);
    }

    /**
     * 创建日期选择器，默认使用 YYYY-MM-DD 格式。
     */
    public static function date(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?? '', FieldType::DATE))
            ->format('YYYY-MM-DD')
            ->valueFormat('YYYY-MM-DD');
    }

    /**
     * 创建日期时间选择器，默认使用 YYYY-MM-DD HH:mm:ss 格式。
     */
    public static function datetime(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?? '', FieldType::DATETIME))
            ->format('YYYY-MM-DD HH:mm:ss')
            ->valueFormat('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * 创建日期范围选择器，默认按开始/结束日期模式输出。
     * 默认提交值是格式化后的日期字符串数组，例如 `['2026-01-01', '2026-01-31']`。
     */
    public static function daterange(string $name, ?string $label = null): DateField
    {
        return (new DateField($name, $label ?? '', FieldType::DATE_RANGE))
            ->format('YYYY-MM-DD')
            ->valueFormat('YYYY-MM-DD')
            ->prop('range-separator', '至')
            ->prop('start-placeholder', '开始日期')
            ->prop('end-placeholder', '结束日期');
    }

    /**
     * 创建开关字段。
     */
    public static function toggle(string $name, ?string $label = null): BasicField
    {
        return new BasicField($name, $label ?? '', FieldType::SWITCH);
    }

    /**
     * 创建隐藏字段，适合传递 id 等不展示值。
     * 隐藏字段仍然参与表单默认值、提交和校验数据结构，只是不渲染输入控件。
     */
    public static function hidden(string $name): BasicField
    {
        return new BasicField($name, $name, FieldType::HIDDEN);
    }
}
