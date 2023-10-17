<?php
/**
 * datetime: 2023/6/4 11:18
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;

trait DefaultValue
{
    protected mixed $default = null;

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        $this->childrenDefault();

        return $this;
    }

    /**
     * @return void
     */
    protected function childrenDefault(): void
    {
        if (!property_exists($this, 'children')){
            return;
        }

        array_map(function (FormItemInterface|FormItemAttrGetter $formItem) {
            // 默认值重设
            if (method_exists($formItem, 'default') && !empty($this->default)) {
                $defaultData = $formItem->getName()
                    ? $this->default[$formItem->getName()] ?? null
                    : $this->default;

                $formItem->default($defaultData);
            }
        }, $this->children);
    }
}