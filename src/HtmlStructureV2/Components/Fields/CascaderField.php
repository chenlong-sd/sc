<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class CascaderField extends OptionField
{
    private array $cascaderProps = [];
    private bool $closeAfterSelection = false;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::CASCADER);
        $this->prop('filterable', true);
    }

    /**
     * 设置 cascader 选项。
     * 当传入树形节点数组时会保留原始结构，结合 cascaderProps() 指定 value/label/children 字段。
     * 同时也支持简单的 `value => label` 选项数组。
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
     * - expandTrigger    次级菜单的展开方式
     * - multiple    是否多选
     * - checkStrictly    是否严格的遵守父子节点不互相关联
     * - emitPath    在选中节点改变时，是否返回由该节点所在的各级菜单的值所组成的数组，若设置 false，则只返回该节点的值
     * - lazy    是否动态加载子节点，需与 lazyLoad 方法结合使用
     * - lazyLoad    加载动态数据的方法，仅在 lazy 为 true 时有效
     * - value    指定选项的值为选项对象的某个属性值
     * - label    指定选项标签为选项对象的某个属性值
     * - children    指定选项的子选项为选项对象的某个属性值
     * - disabled    指定选项的禁用为选项对象的某个属性值
     * - leaf    指定选项的叶子节点的标志位为选项对象的某个属性值
     * - hoverThreshold    hover 时展开菜单的灵敏度阈值
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

    /**
     * 选择完成后自动收起下拉面板。
     * 适合单选级联场景，避免每次选择后仍停留在展开状态。
     *
     * @param bool $closeAfterSelection 是否自动收起，默认值为 true。
     * @return static 当前级联字段实例。
     *
     * 示例：
     * `Fields::cascader('dept_id', '处置部门')->closeAfterSelection()`
     */
    public function closeAfterSelection(bool $closeAfterSelection = true): static
    {
        $this->closeAfterSelection = $closeAfterSelection;

        return $this;
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return ($this->cascaderProps['multiple'] ?? false) ? [] : null;
    }

    public function getCascaderProps(): array
    {
        return $this->cascaderProps;
    }

    public function shouldCloseAfterSelection(): bool
    {
        return $this->closeAfterSelection;
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
