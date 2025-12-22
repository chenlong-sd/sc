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
     * @param string $valueForField
     * @return LinkageUpdate
     */
    public function linkageUpdate(string $currentFormName, string $valueForField): static
    {
        $this->linkageUpdate[$currentFormName] = $valueForField;

        return $this;
    }
}