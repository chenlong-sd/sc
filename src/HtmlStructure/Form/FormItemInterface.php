<?php
/**
 * datetime: 2023/6/3 2:35
 **/

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;

interface FormItemInterface
{
    public function render(string $theme = null): AbstractHtmlElement;

}