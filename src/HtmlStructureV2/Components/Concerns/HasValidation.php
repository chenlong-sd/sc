<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasValidation
{
    protected bool $required = false;
    protected ?array $requiredRule = null;
    protected array $rules = [];

    /**
     * 设置字段为必填，并可自定义提示文案和触发时机。
     *
     * @param bool $required 是否必填，默认值为 true。
     * @param string|null $message 必填提示文案；传 null 时自动生成。
     * @param string|array|null $trigger 校验触发时机；传 null 时使用字段默认触发器。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->required(true, '标题不能为空')`
     */
    public function required(bool $required = true, ?string $message = null, string|array|null $trigger = null): static
    {
        $this->required = $required;
        $this->requiredRule = $required ? [
            'required' => true,
            'message' => $message ?: $this->buildRequiredMessage(),
            'trigger' => $this->normalizeTrigger($trigger),
        ] : null;

        return $this;
    }

    /**
     * 追加一条原始校验规则，会自动补默认 trigger。
     *
     * @param array $rule 原始校验规则。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->rule(['min' => 2, 'message' => '至少 2 个字符'])`
     */
    public function rule(array $rule): static
    {
        if (!array_key_exists('trigger', $rule)) {
            $rule['trigger'] = $this->defaultValidationTrigger();
        }

        $this->rules[] = $rule;

        return $this;
    }

    /**
     * 批量追加多条校验规则。
     *
     * @param array $rules 校验规则列表。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->rules([['min' => 2], ['max' => 20]])`
     */
    public function rules(array $rules): static
    {
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $this->rule($rule);
            }
        }

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
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

    protected function validationPromptPrefix(): string
    {
        return '请输入';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return ['blur', 'change'];
    }
}
