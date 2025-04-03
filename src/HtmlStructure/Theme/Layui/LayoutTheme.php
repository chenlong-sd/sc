<?php

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Layout;
use Sc\Util\HtmlStructure\Theme\Interfaces\LayoutThemeInterface;

/**
 * Class LayoutTheme
 */
class LayoutTheme implements LayoutThemeInterface
{
    /**
     * @param Layout $layout
     *
     * @return AbstractHtmlElement
     */
    public function render(Layout $layout): AbstractHtmlElement
    {
        $el = El::double('div')->addClass('layui-row');

        if (isset($layout->getAttrs()['gutter'])) {
            $el->addClass('layui-col-space' . $layout->getAttrs()['gutter']);
        }
        $el->setAttrs($layout->getAttrs());

        foreach ($layout->getCols() as ['base' => $attrs, 'content' => $content]) {
            if (is_int($attrs)){
                $attrs = ['md' => $attrs];
            }
            $class = [];
            foreach ($attrs as $attr => $value) {
                $class[] = match ($attr) {
                    'xs'     => 'layui-col-xs' . round($value / 2),
                    'sm'     => 'layui-col-sm' . round($value / 2),
                    'md'     => 'layui-col-md' . round($value / 2),
                    'lg'     => 'layui-col-lg' . round($value / 2),
                    'xl'     => 'layui-col-xl' . round($value / 2),
                    'offset' => 'layui-col-offset' . $value,
                    default  => ''
                };
            }

            $el->append(El::double('div')->setAttr('class', implode(' ', $class))->append($content));
        }

        return $el;
    }
}