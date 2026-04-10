<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class CascaderField extends OptionField
{
    private array $cascaderProps = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::CASCADER);
    }

    /**
     * 设置 cascader 选项。
     * 当传入树形节点数组时会保留原始结构，结合 cascaderProps() 指定 value/label/children 字段。
     * 仍兼容简单的 `value => label` 写法。
     *
     * @param array $options 级联选项。
     * @return static 当前级联字段实例。
     *
     * 示例：
     * `Fields::cascader('area', '地区')->options($areaOptions)`
     */
    public function options(array $options): static
    {
        if ($this->shouldKeepRawOptions($options)) {
            $this->options = array_values($options);

            return $this;
        }

        return parent::options($options);
    }

    /**
     * 设置 cascader 的原生 props 配置。
     *
     * @param array $props cascader 原生 props。
     * @return static 当前级联字段实例。
     *
     * 示例：
     * `Fields::cascader('area', '地区')->cascaderProps(['multiple' => true])`
     */
    public function cascaderProps(array $props): static
    {
        $this->cascaderProps = array_merge($this->cascaderProps, $props);
        $this->props[':props'] = $this->cascaderProps;

        if (($this->cascaderProps['multiple'] ?? false) === true && $this->default === null) {
            $this->default = [];
        }

        return $this;
    }

    /**
     * 控制值是否返回完整路径。
     *
     * @param bool $emitPath 是否返回完整路径，默认值为 true。
     * @return static 当前级联字段实例。
     *
     * 示例：
     * `Fields::cascader('area', '地区')->emitPath(false)`
     */
    public function emitPath(bool $emitPath = true): static
    {
        return $this->cascaderProps([
            'emitPath' => $emitPath,
        ]);
    }

    /**
     * 开启严格模式，允许任意层级节点直接选择。
     *
     * @param bool $strict 是否开启严格模式，默认值为 true。
     * @return static 当前级联字段实例。
     *
     * 示例：
     * `Fields::cascader('area', '地区')->checkStrictly()`
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

    private function shouldKeepRawOptions(array $options): bool
    {
        if ($options === [] || !array_is_list($options)) {
            return false;
        }

        foreach ($options as $option) {
            if (!is_array($option)) {
                return false;
            }
        }

        return true;
    }
}
