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
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('email', '邮箱')->validateEmail()`
     * - `Fields::text('email', '邮箱')->validateEmail(null, null, 'model.needEmail === true')`
     * - `Fields::text('contactEmail', '联系邮箱')->validateEmail(null, null, 'model.contactType === "email"')`
     */
    public function validateEmail(?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateRule([
            'type' => 'email',
            'message' => $message ?: '请输入正确的' . $this->label(),
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when);
    }

    /**
     * - `validateEmail()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateEmail() 方法
     */
    public function email(?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateEmail($message, $trigger, $when);
    }

    /**
     * 追加中国大陆手机号格式校验规则。
     *
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('mobile', '手机号')->validatePhone()`
     * - `Fields::text('mobile', '手机号')->validatePhone(null, null, 'model.contactType === "phone"')`
     * - `Fields::text('emergencyPhone', '紧急联系电话')->validatePhone(null, null, 'model.needEmergency === true')`
     */
    public function validatePhone(?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validatePattern(
            '/^1[3-9]\d{9}$/',
            $message ?: '请输入正确的' . $this->label(),
            $trigger,
            $when
        );
    }

    /**
     * - `validatePhone()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validatePhone() 方法
     */
    public function phone(?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validatePhone($message, $trigger, $when);
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
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('code', '编码')->validatePattern('/^[A-Z0-9_-]+$/', '编码格式不正确')`
     * - `Fields::text('id', '身份证号')->validatePattern('/^\d{17}[\dxX]$/', null, null, 'model.needIdCard === true')`
     */
    public function validatePattern(string|JsExpression $pattern, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateRule([
            'pattern' => $pattern instanceof JsExpression ? $pattern : JsExpression::make($pattern),
            'message' => $message ?: $this->label() . '格式不正确',
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when);
    }

    /**
     * - `validatePattern()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validatePattern() 方法
     */
    public function pattern(string|JsExpression $pattern, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validatePattern($pattern, $message, $trigger, $when);
    }

    /**
     * 限制最小字符长度。
     *
     * @param int $length 最小长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateMinLength(2)`
     * - `Fields::text('content', '内容')->validateMinLength(10, null, null, 'model.type === "article"')`
     */
    public function validateMinLength(int $length, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateRule([
            'min' => $length,
            'message' => $message ?: $this->label() . '长度不能少于 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when);
    }

    /**
     * - `validateMinLength()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateMinLength() 方法
     */
    public function minLength(int $length, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateMinLength($length, $message, $trigger, $when);
    }

    /**
     * 限制最大字符长度。
     *
     * @param int $length 最大长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateMaxLength(100)`
     * - `Fields::text('summary', '摘要')->validateMaxLength(200, null, null, 'model.needSummary === true')`
     */
    public function validateMaxLength(int $length, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateRule([
            'max' => $length,
            'message' => $message ?: $this->label() . '长度不能超过 ' . $length,
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when);
    }

    /**
     * - `validateMaxLength()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateMaxLength() 方法
     */
    public function maxLength(int $length, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateMaxLength($length, $message, $trigger, $when);
    }

    /**
     * 限制字符长度范围。
     *
     * @param int $min 最小长度。
     * @param int $max 最大长度。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateLengthBetween(2, 50)`
     * - `Fields::text('nickname', '昵称')->validateLengthBetween(2, 20, null, null, 'model.needNickname === true')`
     */
    public function validateLengthBetween(int $min, int $max, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateRule([
            'min' => $min,
            'max' => $max,
            'message' => $message ?: $this->label() . '长度需在 ' . $min . ' 到 ' . $max . ' 之间',
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when);
    }

    /**
     * - `validateLengthBetween()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateLengthBetween() 方法
     */
    public function lengthBetween(int $min, int $max, ?string $message = null, string|array|null $trigger = null, string|JsExpression|null $when = null): static
    {
        return $this->validateLengthBetween($min, $max, $message, $trigger, $when);
    }

    /**
     * 追加数字格式校验规则（支持整数和浮点数）。
     *
     * @param int $decimals 小数位数；0 表示整数，大于 0 表示浮点数。
     * @param int|float|null $min 最小值；传 null 时不限制。
     * @param int|float|null $max 最大值；传 null 时不限制。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('age', '年龄')->validateNumber(0, 1, 150)` // 整数，1-150
     * - `Fields::text('price', '价格')->validateNumber(2, 0.01)` // 浮点数，最多2位小数，最小0.01
     * - `Fields::text('discount', '折扣')->validateNumber(2, 0, 1, null, null, 'model.hasDiscount === true')`
     */
    public function validateNumber(
        int $decimals = 0,
        int|float|null $min = null,
        int|float|null $max = null,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        // 构建正则表达式
        if ($decimals === 0) {
            // 整数：可选负号 + 数字
            $pattern = '/^-?\d+$/';
            $defaultMessage = $message ?: $this->label() . '必须是整数';
        } else {
            // 浮点数：可选负号 + 数字 + 可选小数部分
            $decimalPart = '{0,' . $decimals . '}';
            $pattern = '/^-?\d+(\.\d' . $decimalPart . ')?$/';
            $defaultMessage = $message ?: $this->label() . '必须是数字（最多' . $decimals . '位小数）';
        }

        // 添加格式校验规则
        $this->validatePattern($pattern, $defaultMessage, $trigger, $when);

        // 添加自定义校验器来处理数值范围
        if ($min !== null || $max !== null) {
            $validatorCode = 'function(rule, value, callback) {';
            $validatorCode .= '  if (value === null || value === undefined || value === "") { callback(); return; }';
            $validatorCode .= '  const num = parseFloat(value);';
            $validatorCode .= '  if (isNaN(num)) { callback(new Error("' . ($message ?: $this->label() . '格式不正确') . '")); return; }';

            if ($min !== null && $max !== null) {
                $validatorCode .= '  if (num < ' . $min . ' || num > ' . $max . ') {';
                $validatorCode .= '    callback(new Error("' . $this->label() . '必须在 ' . $min . ' 到 ' . $max . ' 之间"));';
                $validatorCode .= '  } else { callback(); }';
            } elseif ($min !== null) {
                $validatorCode .= '  if (num < ' . $min . ') {';
                $validatorCode .= '    callback(new Error("' . $this->label() . '不能小于 ' . $min . '"));';
                $validatorCode .= '  } else { callback(); }';
            } else {
                $validatorCode .= '  if (num > ' . $max . ') {';
                $validatorCode .= '    callback(new Error("' . $this->label() . '不能大于 ' . $max . '"));';
                $validatorCode .= '  } else { callback(); }';
            }

            $validatorCode .= '}';

            $this->validateRule([
                'validator' => JsExpression::make($validatorCode),
                'trigger' => $this->normalizeTrigger($trigger),
            ], $when);
        }

        return $this;
    }

    /**
     * 追加整数格式校验规则。
     *
     * @param int|null $min 最小值；传 null 时不限制。
     * @param int|null $max 最大值；传 null 时不限制。
     * @param string|null $message 提示文案；传 null 时使用默认文案。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前文本字段实例。
     *
     * 示例：
     * - `Fields::text('age', '年龄')->validateInteger(1, 150)`
     * - `Fields::text('quantity', '数量')->validateInteger(1, 999, null, null, 'model.needQuantity === true')`
     * - `Fields::text('sort', '排序')->validateInteger(0, 9999, null, null, 'model.enableSort === true')`
     */
    public function validateInteger(
        ?int $min = null,
        ?int $max = null,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        return $this->validateNumber(0, $min, $max, $message, $trigger, $when);
    }

    /**
     * - `validateNumber()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateNumber() 方法
     */
    public function number(
        int $decimals = 0,
        int|float|null $min = null,
        int|float|null $max = null,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        return $this->validateNumber($decimals, $min, $max, $message, $trigger, $when);
    }

    /**
     * - `validateInteger()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateInteger() 方法
     */
    public function integer(
        ?int $min = null,
        ?int $max = null,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        return $this->validateInteger($min, $max, $message, $trigger, $when);
    }
}
