<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class CascaderField extends OptionField
{
    private array $cascaderProps = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::CASCADER);
    }

    /**
     * 设置 cascader 的原生 props 配置。
     */
    public function cascaderProps(array $props): static
    {
        $this->cascaderProps = array_merge($this->cascaderProps, $props);
        $this->props[':props'] = JsonExpressionEncoder::encodeCompact($this->cascaderProps);

        if (($this->cascaderProps['multiple'] ?? false) === true && $this->default === null) {
            $this->default = [];
        }

        return $this;
    }

    /**
     * 控制值是否返回完整路径。
     */
    public function emitPath(bool $emitPath = true): static
    {
        return $this->cascaderProps([
            'emitPath' => $emitPath,
        ]);
    }

    /**
     * 开启严格模式，允许任意层级节点直接选择。
     */
    public function checkStrictly(bool $strict = true): static
    {
        return $this->cascaderProps([
            'checkStrictly' => $strict,
        ]);
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return ($this->cascaderProps['multiple'] ?? false) ? [] : null;
    }
}
