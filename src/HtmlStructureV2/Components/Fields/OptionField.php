<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

class OptionField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    protected array $options = [];
    protected ?array $remoteOptions = null;
    protected array $remoteOptionDependencies = [];
    protected bool $remoteOptionsClearOnChange = true;
    protected array $linkageUpdates = [];
    protected bool $linkageClearOnEmpty = true;

    public function __construct(string $name, string $label, FieldType $type = FieldType::SELECT)
    {
        parent::__construct($name, $label, $type);
    }

    /**
     * 设置静态选项列表，支持 value => label 或完整选项数组格式。
     * 完整选项数组至少包含 `value` / `label`，也可附带 `disabled` 或其它扩展字段，
     * 这些扩展字段可在 linkageUpdate() 中通过 "@option.xxx" 读取。
     */
    public function options(array $options): static
    {
        $this->options = [];

        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label) && array_key_exists('label', $label)) {
                $this->options[] = $label;
                continue;
            }

            $this->options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $this;
    }

    /**
     * 配置远端选项加载接口及值/标签字段映射。
     * `params` 中以 "@" 开头的顶层值会从当前表单 model 读取，
     * 例如 `['dept_id' => "@dept_id"]`。返回列表会按 valueField/labelField 归一化成标准选项。
     * 这里默认只有当前作用域下的 form model 可用，不会注入 row / tableKey / listKey / vm。
     */
    public function remoteOptions(
        string $url,
        string $valueField = 'id',
        string $labelField = 'name',
        array $params = []
    ): static {
        $this->remoteOptions = [
            'url' => $url,
            'method' => 'get',
            'valueField' => $valueField,
            'labelField' => $labelField,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * 设置远端选项加载请求方法。
     * 默认是 `get`，也可改成 `post` / `put` 等。
     */
    public function remoteOptionsMethod(string $method): static
    {
        if ($this->remoteOptions === null) {
            $this->remoteOptions = [];
        }

        $this->remoteOptions['method'] = strtolower($method);

        return $this;
    }

    /**
     * 声明远端选项依赖的表单字段，依赖变更时可重新拉取。
     * 字段路径相对当前表单作用域解析；在数组行里会自动映射到当前行上下文。
     * 依赖字段为空时，请求不会触发。
     */
    public function remoteOptionsDependsOn(string ...$fields): static
    {
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '' || in_array($field, $this->remoteOptionDependencies, true)) {
                continue;
            }

            $this->remoteOptionDependencies[] = $field;
        }

        return $this;
    }

    /**
     * 控制依赖字段变化时是否清空当前选中值。
     * 默认开启，避免旧值与新选项集不一致。
     */
    public function remoteOptionsClearOnChange(bool $clear = true): static
    {
        $this->remoteOptionsClearOnChange = $clear;

        return $this;
    }

    /**
     * 配置联动更新，把当前选项的值同步到其它字段。
     * 常用模板：
     * - "@value": 当前选中值
     * - "@label": 当前选中文案
     * - "@model.xxx": 当前表单其它字段
     * - "@option.xxx": 当前选项对象上的扩展字段
     * 也支持把这些 token 混入普通字符串，例如 "部门：@label"。
     *
     * 若传 JsExpression，运行时只接收一个 context 对象，包含：
     * - scope: 当前表单作用域
     * - fieldName: 当前字段名
     * - value: 当前选中值
     * - option: 当前命中的标准化选项对象
     * - model: 当前作用域下的表单模型
     * 当前联动主要用于 select/radio 的 change 行为。
     */
    public function linkageUpdate(string $targetField, string|JsExpression $valueTemplate = '@label'): static
    {
        $this->linkageUpdates[$targetField] = $valueTemplate;

        return $this;
    }

    /**
     * 批量配置联动更新规则。
     * 每条规则的模板语义与 linkageUpdate() 完全一致。
     */
    public function linkageUpdates(array $updates): static
    {
        foreach ($updates as $targetField => $valueTemplate) {
            if (!is_string($targetField)) {
                continue;
            }

            if (!is_string($valueTemplate) && !$valueTemplate instanceof JsExpression) {
                continue;
            }

            $this->linkageUpdate($targetField, $valueTemplate);
        }

        return $this;
    }

    /**
     * 控制选项清空时是否同步清空联动目标字段。
     * 默认开启，适合“选择上级后自动填充下级字段”的场景。
     */
    public function linkageClearOnEmpty(bool $clear = true): static
    {
        $this->linkageClearOnEmpty = $clear;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasRemoteOptions(): bool
    {
        return $this->remoteOptions !== null;
    }

    public function getRemoteOptions(): ?array
    {
        if ($this->remoteOptions === null) {
            return null;
        }

        return array_merge($this->remoteOptions, [
            'dependencies' => $this->getRemoteOptionDependencies(),
            'clearOnChange' => $this->remoteOptionsClearOnChange,
        ]);
    }

    public function hasLinkageUpdates(): bool
    {
        return $this->linkageUpdates !== [];
    }

    public function getLinkageConfig(): ?array
    {
        if (!$this->hasLinkageUpdates()) {
            return null;
        }

        return [
            'updates' => $this->linkageUpdates,
            'clearOnEmpty' => $this->linkageClearOnEmpty,
        ];
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return $this->type() === FieldType::CHECKBOX ? [] : null;
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
    private function getRemoteOptionDependencies(): array
    {
        $dependencies = $this->remoteOptionDependencies;

        foreach (($this->remoteOptions['params'] ?? []) as $value) {
            $this->collectRemoteDependenciesFromValue($value, $dependencies);
        }

        return array_values(array_unique($dependencies));
    }

    private function collectRemoteDependenciesFromValue(mixed $value, array &$dependencies): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectRemoteDependenciesFromValue($item, $dependencies);
            }

            return;
        }

        if (!is_string($value) || !str_starts_with($value, '@')) {
            return;
        }

        $field = trim(substr($value, 1));
        if ($field !== '') {
            $dependencies[] = $field;
        }
    }
}
