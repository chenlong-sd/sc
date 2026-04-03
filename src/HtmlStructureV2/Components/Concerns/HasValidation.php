<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasValidation
{
    protected bool $required = false;
    protected ?array $requiredRule = null;
    protected array $rules = [];

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

    public function rule(array $rule): static
    {
        if (!array_key_exists('trigger', $rule)) {
            $rule['trigger'] = $this->defaultValidationTrigger();
        }

        $this->rules[] = $rule;

        return $this;
    }

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
