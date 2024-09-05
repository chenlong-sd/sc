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
class FormItemPassword extends FormItemText
{
    public function __construct(?string $name = null, ?string $label = null)
    {
        parent::__construct($name, $label);

        $this->toPassword();
    }
}