<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

class TextField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    public function __construct(string $name, string $label, FieldType $type = FieldType::TEXT)
    {
        parent::__construct($name, $label, $type);
    }

    /**
     * 追加邮箱格式校验规则。
     *
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('email', '邮箱')->email()`
     */
    public function email(?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'type' => 'email',
            'message' => $message ?: '请输入正确的' . $this->label(),
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    /**
     * 追加中国大陆手机号格式校验规则。
     *
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('mobile', '手机号')->phone()`
     */
    public function phone(?string $message = null, string|array|null $trigger = null): static
    {
        return $this->pattern(
            '/^1[3-9]\d{9}$/',
            $message ?: '请输入正确的' . $this->label(),
            $trigger
        );
    }

    /**
     * 追加正则格式校验规则。
     * 这里使用的是前端 JS 正则表达式，不是 PHP `preg_*` 语法。
     * 最常见写法是传 JS 正则字面量字符串，例如 `'/^1[3-9]\\d{9}$/'`。
     * 这里不会注入额外运行时上下文，只是单纯把 pattern 当成前端正则或正则表达式处理。
     *
     * @param string|JsExpression $pattern 前端 JS 正则表达式。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('code', '编码')->pattern('/^[A-Z0-9_-]+$/', '编码格式不正确')`
     */
    public function pattern(string|JsExpression $pattern, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'pattern' => $pattern instanceof JsExpression ? $pattern : JsExpression::make($pattern),
            'message' => $message ?: $this->label() . '格式不正确',
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    /**
     * 限制最小字符长度。
     *
     * @param int $length 最小长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->minLength(2)`
     */
    public function minLength(int $length, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'min' => $length,
            'message' => $message ?: $this->label() . '长度不能少于 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    /**
     * 限制最大字符长度。
     *
     * @param int $length 最大长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->maxLength(100)`
     */
    public function maxLength(int $length, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'max' => $length,
            'message' => $message ?: $this->label() . '长度不能超过 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }

    /**
     * 限制字符长度范围。
     *
     * @param int $min 最小长度。
     * @param int $max 最大长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->lengthBetween(2, 50)`
     */
    public function lengthBetween(int $min, int $max, ?string $message = null, string|array|null $trigger = null): static
    {
        return $this->rule([
            'min' => $min,
            'max' => $max,
            'message' => $message ?: $this->label() . '长度需在 ' . $min . ' 到 ' . $max . ' 之间',
            'trigger' => $this->normalizeTrigger($trigger),
        ]);
    }
}
