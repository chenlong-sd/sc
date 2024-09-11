<?php
/**
 * datetime: 2023/6/3 2:47
 **/

namespace Sc\Util\HtmlStructure\Form;

/**
 * Class FormItemPassword
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
class FormItemNumber extends FormItemText
{
    public function __construct(?string $name = null, ?string $label = null)
    {
        parent::__construct($name, $label);

        $this->toNumber();
    }

    /**
     * 设置小数精度
     *
     * @param int $precision ,小于0则是无限制
     *
     * @return $this
     */
    public function setPrecision(int $precision = -1): static
    {
        $this->numberPrecision = $precision;
        return $this;
    }
}