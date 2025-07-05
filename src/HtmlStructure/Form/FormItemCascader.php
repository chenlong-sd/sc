<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Options;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemCascaderThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemCascader
 */
class FormItemCascader extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Placeholder, Options, LabelWidth, FormOrigin, Events, Attrs,Validate;

    /**
     * @var true
     */
    protected bool $closeAfterSelection = false;
    /**
     * @var true
     */
    private bool $isPanel = false;

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemCascaderThemeInterface::class, $theme)->render($this);
    }

    /**
     * 转为面板模式
     *
     * @return $this
     */
    public function toPanel(): static
    {
        $this->isPanel = true;

        return $this;
    }

    /**
     * 选择后即关闭
     *
     * @return $this
     */
    public function closeAfterSelection(): static
    {
        $this->closeAfterSelection = true;

        return $this;
    }

    public function isPanel(): bool
    {
        return $this->isPanel;
    }

    /**
     * @param array $props
     * <li> - expandTrigger    次级菜单的展开方式</li>
     * <li> - multiple    是否多选</li>
     * <li> - checkStrictly    是否严格的遵守父子节点不互相关联</li>
     * <li> - emitPath    在选中节点改变时，是否返回由该节点所在的各级菜单的值所组成的数组，若设置 false，则只返回该节点的值</li>
     * <li> - lazy    是否动态加载子节点，需与 lazyLoad 方法结合使用</li>
     * <li> - lazyLoad    加载动态数据的方法，仅在 lazy 为 true 时有效</li>
     * <li> - value    指定选项的值为选项对象的某个属性值</li>
     * <li> - label    指定选项标签为选项对象的某个属性值</li>
     * <li> - children    指定选项的子选项为选项对象的某个属性值</li>
     * <li> - disabled    指定选项的禁用为选项对象的某个属性值</li>
     * <li> - leaf    指定选项的叶子节点的标志位为选项对象的某个属性值</li>
     * <li> - hoverThreshold    hover 时展开菜单的灵敏度阈值</li>
     * @return $this
     * @see https://element-plus.org/zh-CN/component/cascader.html#cascaderprops
     */
    public function props(array $props): static
    {
        $this->setVAttrs(":props", strtr(json_encode($props), ['"' => '\'']));
        return $this;
    }
}