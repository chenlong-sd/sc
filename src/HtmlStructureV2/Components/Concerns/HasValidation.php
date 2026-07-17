<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasValidation
{
    protected bool $required = false;
    protected bool $conditionalRequired = false;
    protected ?string $requiredCondition = null;
    protected ?array $requiredRule = null;
    protected array $rules = [];

    /**
     * 设置字段为必填，并可自定义提示文案和触发时机。
     *
     * @param bool $required 是否必填，默认值为 true。
     * @param string|null $message 必填提示文案；传 null 时自动生成。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证，否则只在条件为真时验证。 表达式上下文与 visibleWhen() 一致，当前默认可用变量包括：
     *   - `model`：当前字段所在的局部模型；顶层字段时通常等于整个 form， object 分组里是当前对象，arrayGroup/table 行内通常是当前行对象。
     *   - `form`：当前表单根模型，可读取当前表单任意字段。
     *   - `state`：当前页面运行时 state，包含 `Pages::state()` 与 `Forms::state()` 数据。
     *   - `pageState`：`state` 的语义化别名，当前实现里两者指向同一份对象。
     *   - `scope`：当前表单 scope / key，例如 `article-form`。
     *   - `dialogRow`：当字段渲染在弹窗 body/footer 内时可用，表示打开弹窗的当前表格行数据；
     *     它不属于表单 `model`，不会随表单提交，适合只用于条件校验判断。
     *   - `fieldName`：当前字段完整路径，例如 `status`、`profile.dept_id`。
     *   - `vm`：当前页面根 Vue 实例 / runtime 宿主对象。
     *   - `options`：当前字段的已解析选项数组；非选项字段通常为空数组。
     *   - `fieldConfig`：当前字段运行时配置；对动态/远端选项字段尤其有用。
     *   - `optionLoading`：当前字段选项是否正在加载。
     *   - `optionLoaded`：当前字段选项是否至少完成过一次加载/写入。
     *   - `field`：当前字段静态元信息快照，至少包含 name/path/label/type/props 等。
     *   - `props`：`field.props` 的快捷别名，可直接判断 `props.multiple` 等配置。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateRequired(true, '标题不能为空')`
     * - `Fields::text('other', '其他')->validateRequired(true, null, null, 'model.type === "other"')`
     * - `Fields::text('other', '其他')->validateRequired(true, null, null, 'dialogRow?.business_type?.scene == 1')`
     * - `Fields::text('remark', '备注')->validateRequired(true, null, null, 'model.needRemark === true')`
     */
    public function validateRequired(
        bool $required = true,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        $this->required = $required;
        // 标记是否是条件必填，并保存条件表达式
        $this->conditionalRequired = ($when !== null && $required);
        if ($this->conditionalRequired) {
            $this->requiredCondition = $when instanceof JsExpression ? $when->expression() : $when;
        }

        $this->requiredRule = $required ? $this->buildRule([
            'required' => true,
            'message' => $message ?: $this->buildRequiredMessage(),
            'trigger' => $this->normalizeTrigger($trigger),
        ], $when) : null;

        return $this;
    }

    /**
     * - `validateRequired()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateRequired() 方法
     */
    public function required(
        bool $required = true,
        ?string $message = null,
        string|array|null $trigger = null,
        string|JsExpression|null $when = null
    ): static {
        return $this->validateRequired($required, $message, $trigger, $when);
    }

    /**
     * 追加一条原始校验规则，会自动补默认 trigger。
     *
     * @param array $rule 原始校验规则。
     * @param string|JsExpression|null $when 条件表达式；传 null 时始终验证，否则只在条件为真时验证。
     *                                       表达式上下文与 validateRequired() / visibleWhen() 完全一致，
     *                                       详细变量说明见 validateRequired() 注释。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateRule(['min' => 2, 'message' => '至少 2 个字符'])`
     * - `Fields::text('price', '价格')->validateRule(['type' => 'number'], 'model.needPrice === true')`
     * - `Fields::text('remark', '备注')->validateRule(['max' => 100], 'dialogRow?.status == 1')`
     * - `Fields::text('desc', '描述')->validateRule(['max' => 100], 'model.category === "brief"')`
     */
    public function validateRule(array $rule, string|JsExpression|null $when = null): static
    {
        if (!array_key_exists('trigger', $rule)) {
            $rule['trigger'] = $this->defaultValidationTrigger();
        }

        $this->rules[] = $this->buildRule($rule, $when);

        return $this;
    }

    /**
     * - `validateRule()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateRule() 方法
     */
    public function rule(array $rule, string|JsExpression|null $when = null): static
    {
        return $this->validateRule($rule, $when);
    }

    /**
     * 批量追加多条校验规则。
     *
     * @param array $rules 校验规则列表。
     * @return static 当前字段实例。
     *
     * 示例：
     * - `Fields::text('title', '标题')->validateRules([['min' => 2], ['max' => 20]])`
     */
    public function validateRules(array $rules): static
    {
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $this->validateRule($rule);
            }
        }

        return $this;
    }

    /**
     * - `validateRules()` 的别名方法，保持向后兼容。
     *
     * @deprecated 建议使用 validateRules() 方法
     */
    public function rules(array $rules): static
    {
        return $this->validateRules($rules);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isConditionalRequired(): bool
    {
        return $this->conditionalRequired;
    }

    public function getRequiredCondition(): ?string
    {
        return $this->requiredCondition;
    }

    public function hasRules(): bool
    {
        return $this->requiredRule !== null || $this->rules !== [];
    }

    public function getRules(): array
    {
        $rules = $this->rules;

        if ($this->requiredRule !== null) {
            array_unshift($rules, $this->requiredRule);
        }

        return $rules;
    }

    protected function buildRequiredMessage(): string
    {
        return $this->validationPromptPrefix() . $this->label();
    }

    protected function normalizeTrigger(string|array|null $trigger): string|array
    {
        return $trigger ?? $this->defaultValidationTrigger();
    }

    /**
     * 构建验证规则，如果提供了 when 条件，将条件表达式存储在规则的 __when__ 属性中。
     * 前端会通过 Vue 的 computed 来动态处理条件验证。
     */
    protected function buildRule(array $rule, string|JsExpression|null $when): array
    {
        if ($when === null) {
            return $rule;
        }

        $condition = $when instanceof JsExpression ? $when->expression() : $when;

        // 将条件表达式存储为字符串，而不是 JsExpression
        // 这样在序列化时不会被直接执行
        $rule['__when__'] = $condition;

        return $rule;
    }

    protected function validationPromptPrefix(): string
    {
        return '请输入';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return ['blur', 'change'];
    }
}
