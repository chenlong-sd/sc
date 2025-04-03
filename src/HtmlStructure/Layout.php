<?php

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Theme\Interfaces\LayoutThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * 布局容器
 *
 * Class Layout
 */
class Layout
{
    protected array $cols = [];
    protected array $attrs = [];

    /**
     * @param array|int|null $attrs
     * @param int|null       $width
     *
     * @return Layout
     */
    public static function create(array|int $attrs = null, int $width = null): Layout
    {
        $layout = new self();

        if ($attrs) {
            $layout->setAttrs(is_int($attrs) ? ['gutter' => $attrs] : $attrs);
        }

        if ($width) {
            $layout->setAttrs(['style', "width:{$width}px"]);
        }

        return $layout;
    }

    /**
     * 添加一列
     *
     * @param int|array                                   $span 位数字时为默认占比，数组时则支持对应主题的属性,最大值 24
     *                                                          基础：     ['span' => 4]
     *                                                          响应式时：  ['xs'=>4, 'sm'=>6, 'md'=>8, 'lg'=>9, 'xl'=>11,]
     *                                                          偏移量：   ['offset' => 10]
     * @param AbstractHtmlElement|string|\Stringable|null $htmlElement
     *
     * @return Layout
     */
    public function addCol(int|array $span, AbstractHtmlElement|string|\Stringable $htmlElement = null): static
    {
        $this->cols[] = [
            'base'    => $span,
            'content' => $htmlElement
        ];

        return $this;
    }

    /**
     * @param array|string $attrs
     * @param mixed|null   $value
     *
     * @return Layout
     */
    public function setAttrs(#[ExpectedValues([
        'gutter', // 列间距
    ])] array|string $attrs, mixed $value = null): Layout
    {
        if (is_string($attrs)) {
            $attrs = [$attrs => $value];
        }

        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    /**
     * @param string|null $theme
     *
     * @return AbstractHtmlElement
     */
    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(LayoutThemeInterface::class, $theme)->render($this);
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * @return array
     */
    public function getCols(): array
    {
        return $this->cols;
    }
}