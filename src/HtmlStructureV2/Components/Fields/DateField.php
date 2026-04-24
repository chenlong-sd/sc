<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use JetBrains\PhpStorm\ExpectedValues;
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

    public const SUPPORTED_PICKER_TYPES = [
        'date',
        'time',
        'dates',
        'datetime',
        'month',
        'year',
        'week',
        'datetimerange',
        'daterange',
        'monthrange',
    ];

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

    /**
     * 设置时间选择器类型。
     * 统一支持单值 / 区间模式，例如 `date`、`datetime`、`daterange`、`datetimerange`。
     * 切换类型时会同步切到对应默认格式；如需自定义格式，请在当前方法后继续链式调用 `format()` / `valueFormat()`。
     *
     * @param string $type 选择器类型。
     * @return static 当前日期字段实例。
     *
     * 示例：
     * `Fields::datetime('service_time', '服务时间')->pickerType('datetimerange')`
     */
    public function pickerType(
        #[ExpectedValues(self::SUPPORTED_PICKER_TYPES)]
        string $type
    ): static {
        $type = strtolower(trim($type));
        if ($type === '') {
            return $this;
        }

        $this->prop('type', $type);

        return match ($type) {
            'date', 'dates', 'daterange' => $this
                ->format('YYYY-MM-DD')
                ->valueFormat('YYYY-MM-DD'),
            'month', 'monthrange' => $this
                ->format('YYYY-MM')
                ->valueFormat('YYYY-MM'),
            'year' => $this
                ->format('YYYY')
                ->valueFormat('YYYY'),
            'week' => $this
                ->prop('format', null)
                ->prop('value-format', null),
            'time' => $this
                ->format('HH:mm:ss')
                ->valueFormat('HH:mm:ss'),
            default => $this
                ->format('YYYY-MM-DD HH:mm:ss')
                ->valueFormat('YYYY-MM-DD HH:mm:ss'),
        };
    }

    /**
     * 旧版时间类型写法的兼容别名。
     *
     * @param string $type 选择器类型。
     * @return static 当前日期字段实例。
     */
    public function setTimeType(
        #[ExpectedValues(self::SUPPORTED_PICKER_TYPES)]
        string $type
    ): static {
        return $this->pickerType($type);
    }

    public function getPickerType(): string
    {
        $type = strtolower(trim((string)($this->getProps()['type'] ?? '')));
        if ($type !== '') {
            return $type;
        }

        return match ($this->type()) {
            FieldType::DATE => 'date',
            FieldType::DATE_RANGE => 'daterange',
            default => 'datetime',
        };
    }

    public function isRangePicker(): bool
    {
        return in_array($this->getPickerType(), ['daterange', 'datetimerange', 'monthrange'], true);
    }

    public function usesTimePicker(): bool
    {
        return $this->getPickerType() === 'time';
    }
}
