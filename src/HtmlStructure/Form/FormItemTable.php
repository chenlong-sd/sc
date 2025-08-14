<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTableThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemLine
 */
class FormItemTable extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, FormOrigin, LabelWidth;

    /**
     * @var FormItemInterface[]|AbstractFormItem[]|FormItemAttrGetter[]
     */
    protected array $children = [];

    protected array $columnAttrs = [];

    protected string $lazyLoadFuncName = '';

    public function render(string $theme = null): AbstractHtmlElement
    {
        foreach ($this->children as $item) {
            if ($item instanceof FormItemGroup){
                throw new \Exception('table类型不能嵌套group类型');
            }
        }

        return Theme::getRenderer(FormItemTableThemeInterface::class, $theme)->render($this);
    }

    /**
     * @param FormItemInterface ...$formItem
     *
     * @return $this
     */
    public function addItems(FormItemInterface ...$formItem): FormItemTable
    {
        $this->children = array_merge($this->children, $formItem);
        return $this;
    }

    public function setColumnAttrs(int $index, array $columnAttrs): static
    {
        $this->columnAttrs[$index] = $columnAttrs;

        return $this;
    }

    public function getColumnAttrs(int $index = null): array
    {
        if ($index === null) {
            return $this->columnAttrs;
        }

        return $this->columnAttrs[$index] ?? [];
    }

    public function getLazyLoadFuncName(): string
    {
        return $this->lazyLoadFuncName;
    }

    protected function getDefault()
    {
        if ($this->default){
            foreach ($this->default as $index => &$value){
                $value['_id_'] = "t-" . $index;
            }

            unset($value);
            return $this->default;
        }

        return $this->noFormDefaultData();
    }


    public function setFormModel(string $formModel): void
    {
        $this->formModel = $formModel;

        $this->childrenFormSet('setFormModel', "scope.row");
    }

    /**
     * @return array
     */
    private function noFormDefaultData(): array
    {
        $rowData = array_merge(...array_map(function ($v) {
            if ($v->getName()) {
                return [$v->getName() => $v->getDefault()];
            }

            return $v->getDefault();
        }, array_filter($this->getChildren(), fn($v) => !$v instanceof FormItemSubmit && !$v instanceof FormItemCustomize)));

        $rowData['_id_'] = 1;

        return [$rowData];
    }


    public function getChildren(): array
    {
        return array_filter($this->children, fn($children) => !$children->getHide());
    }

    public function readonly(string $when = ''): static
    {
        foreach ($this->getChildren() as $child) {
            $child->readonly($when);
        }

        return $this;
    }


    /**
     * 懒加载
     *
     * @param string $lazyLoadFuncName 加载表单函数名
     *
     * @return static
     */
    public function lazyLoad(string $lazyLoadFuncName): static
    {
        $this->lazyLoadFuncName = $lazyLoadFuncName;

        return $this;
    }
}