<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class DateField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    public function __construct(string $name, string $label, FieldType $type = FieldType::DATE)
    {
        parent::__construct($name, $label, $type);
    }

    protected function defaultPromptPrefix(): string
    {
        return '请选择';
    }

    protected function validationPromptPrefix(): string
    {
        return '请选择';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return 'change';
    }

    /**
     * 设置日期组件显示格式。
     * 使用 Element Plus / dayjs 风格格式字符串，只影响组件展示，不改变实际提交值。
     *
     * @param string $format 展示格式。
     * @return static 当前日期字段实例。
     *
     * 示例：
     * `Fields::date('publish_date', '发布日期')->format('YYYY/MM/DD')`
     */
    public function format(string $format): static
    {
        return $this->prop('format', $format);
    }

    /**
     * 设置提交到表单模型中的值格式。
     * 设置后，表单模型里存的是格式化后的字符串；不设置时通常保留原生 DatePicker 默认值。
     *
     * @param string $format 提交值格式。
     * @return static 当前日期字段实例。
     *
     * 示例：
     * `Fields::datetime('published_at', '发布时间')->valueFormat('YYYY-MM-DD HH:mm:ss')`
     */
    public function valueFormat(string $format): static
    {
        return $this->prop('value-format', $format);
    }
}
