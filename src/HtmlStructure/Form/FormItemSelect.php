<?php

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Options;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Placeholder;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use Sc\Util\Tool\Url;

/**
 * Class FormItemSelect
 */
class FormItemSelect extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs;

    /**
     * @var true
     */
    protected bool $multiple = false;
    protected array $remoteSearch = [];

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemSelectThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * @return $this
     */
    public function multiple(): static
    {
        $this->multiple = true;

        return $this;
    }

    /**
     * @param string|Url                  $url
     * @param string|\Closure|JsFunc|null $fieldOrCode 为字符串时识别为搜索的字段，否则为搜索处理代码
     * @param string|null                 $defaultSearchField 有默认值时搜索值
     *
     * @return $this
     */
    public function remoteSearch(string|Url $url,  #[Language('JavaScript')]string|\Closure|JsFunc $fieldOrCode = null, string $defaultSearchField = null): static
    {
        $code = $fieldOrCode instanceof \Closure ? $fieldOrCode() : $fieldOrCode;

        $this->remoteSearch = [
            'url' => $url,
            'code' => is_array($code) ? $code[0] : $code,
            'defaultSearchField' => is_array($code) ? $code[1] : ($defaultSearchField)
        ];

        return $this;
    }
}