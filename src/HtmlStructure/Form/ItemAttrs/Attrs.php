<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlElement\El;

trait Attrs
{
    protected array $attrs = [];

    /**
     * 表单元素属性,该属性作用于v-model所属标签，layui则是name所属标签
     *
     * @param array|string $attr
     * @param mixed        $value
     *
     * @return $this
     */
    public function setVAttrs(array|string $attr, mixed $value = ''): static
    {
        if (!is_array($attr)){
            $attr = $value === '' ? El::getAttrFromStr($attr) : [$attr => $value];
        }

        foreach ($attr as $key => &$value) {
            if (is_bool($value)){
                $value = $value ? 'true' : 'false';
            }
        }

        $this->attrs = array_merge($this->attrs, $attr);

        return $this;
    }

    /**
     * @return array
     */
    protected function getVAttrs(): array
    {
        return $this->attrs;
    }
}