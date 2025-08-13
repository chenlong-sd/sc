<?php

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Options;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Slots;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use Sc\Util\Tool\Url;

/**
 * Class FormItemSelect
 */
class FormItemSelect extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs, Validate, Slots;

    /**
     * @var true
     */
    protected bool $multiple = false;
    protected array $remoteSearch = [];

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemSelectThemeInterface::class, $theme)->render($this);
    }

    public function addEmptyValues(...$value): static
    {
        $this->setVAttrs(':empty-values', json_encode([...$value, null]));
        $this->setVAttrs(':value-on-clear', current($value));
        return $this;
    }

    /**
     * @return $this
     */
    public function multiple(): static
    {
        $this->multiple = true;
        $this->default($this->default ?: []);

        return $this;
    }

    public function default(mixed $default): static
    {
        if ($this->multiple && !is_array($default)) {
            $default = [];
        }
        $this->setInitDefault($default);
        $this->default = $default;

        return $this;
    }

    /**
     * @param string|Url                  $url
     * @param string|\Closure|JsFunc|null $searchFieldOrCode      为字符串时识别为搜索和显示的字段，否则为搜索处理代码
     * @param string|null                 $haveDefaultSearchField 该表单有默认值时远程搜索的字段名，默认为id
     * @param string|null                 $afterSearchHandle      搜索之后的处理,结果数据为data
     *
     * @return $this
     */
    public function remoteSearch(string|Url $url, #[Language('JavaScript')]string|\Closure|JsFunc $searchFieldOrCode = null, string $haveDefaultSearchField = null, #[Language('JavaScript')] string $afterSearchHandle = null): static
    {
        $code = $searchFieldOrCode instanceof \Closure ? $searchFieldOrCode() : $searchFieldOrCode;

        $this->remoteSearch = [
            'url' => $url,
            'code' => is_array($code) ? $code[0] : $code,
            'defaultSearchField' => is_array($code) ? $code[1] : ($haveDefaultSearchField),
            'afterSearchHandle' => $afterSearchHandle,
        ];

        return $this;
    }


    public function readonly(string $when = ''): static
    {
        return $this->setVAttrs(':disabled', $when ?: 'true');
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