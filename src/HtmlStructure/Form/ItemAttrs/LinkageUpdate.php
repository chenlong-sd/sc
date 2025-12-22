<?php

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

/**
 * 联动更新
 */
trait LinkageUpdate
{
    protected array $linkageUpdate = [];

    /**
     * @param string $currentFormName 当前表单的name
     * @param string $valueForField 取值字段，选择的值对象对应同名字段如 [{value:1,label:"测试"}], valueForField为label时，选中后的值则为测试
     * @return LinkageUpdate
     */
    public function linkageUpdate(string $currentFormName, string $valueForField = 'label'): static
    {
        $this->linkageUpdate[$currentFormName] = $valueForField;

        return $this;
    }
}