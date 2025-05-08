<?php
/**
 * datetime: 2023/6/4 11:18
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemTable;

trait DefaultValue
{
    protected mixed $initDefault = null;

    protected mixed $default = null;

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function default(mixed $default): static
    {
        $this->setInitDefault($default);
        $this->default = $default;

        $this->childrenDefault();

        return $this;
    }

    /**
     * @return void
     */
    protected function childrenDefault(): void
    {
        if (!property_exists($this, 'children') || $this instanceof FormItemTable){
            return;
        }

        array_map(function (FormItemInterface|FormItemAttrGetter $formItem) {
            // 默认值重设
            if (method_exists($formItem, 'default') && $this->default !== null) {
                $defaultData = $formItem->getName()
                    ? $this->default[$formItem->getName()] ?? null
                    : $this->default;

                $formItem->default($defaultData);
            }
        }, $this->children);
    }

    /**
     * @param mixed $default
     * @return void
     */
    protected function setInitDefault(mixed $default): void
    {
        if ($this->initDefault === null) {
            $this->initDefault = property_exists($this, 'children') ? $this->getDefault() : $default;
        }
    }
}