<?php
/**
 * datetime: 2023/6/3 2:38
 **/

namespace Sc\Util\HtmlStructure\Theme\Interfaces;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form;

/**
 * 表单渲染
 *
 * Interface FormThemeInterface
 *
 * @package Sc\Util\HtmlStructure\Theme\Interfaces
 * @date    2023/6/3
 */
interface FormThemeInterface
{
    /**
     * @param Form $form
     *
     * @return AbstractHtmlElement
     * @date 2023/6/3
     */
    public function render(Form $form): AbstractHtmlElement;
}