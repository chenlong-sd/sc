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
use Sc\Util\HtmlStructure\Form\ItemAttrs\Validate;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use Sc\Util\Tool\Url;

/**
 * Class FormItemSelect
 */
class FormItemSelect extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs, Validate;

    /**
     * @var true
     */
    protected bool $multiple = false;
    protected array $remoteSearch = [];

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemSelectThemeInterface::class, $theme)->render($this);
    }

    public function addEmptyValues(...$value): static
    {
        $this->setVAttrs(':empty-values', json_encode([...$value, null]));
        $this->setVAttrs(':value-on-clear', current($value));
        return $this;
    }

    /**
     * @return $this
     */
    public function multiple(): static
    {
        $this->multiple = true;
        $this->default($this->default ?: []);

        return $this;
    }

    public function default(mixed $default): static
    {
        if ($this->multiple && !is_array($default)) {
            $default = [];
        }
        $this->default = $default;

        return $this;
    }

    /**
     * @param string|Url                  $url
     * @param string|\Closure|JsFunc|null $searchFieldOrCode      为字符串时识别为搜索和显示的字段，否则为搜索处理代码
     * @param string|null                 $haveDefaultSearchField 该表单有默认值时远程搜索的字段名，默认为id
     * @param string|null                 $afterSearchHandle      搜索之后的处理,结果数据为data
     *
     * @return $this
     */
    public function remoteSearch(string|Url $url, #[Language('JavaScript')]string|\Closure|JsFunc $searchFieldOrCode = null, string $haveDefaultSearchField = null, #[Language('JavaScript')] string $afterSearchHandle = null): static
    {
        $code = $searchFieldOrCode instanceof \Closure ? $searchFieldOrCode() : $searchFieldOrCode;

        $this->remoteSearch = [
            'url' => $url,
            'code' => is_array($code) ? $code[0] : $code,
            'defaultSearchField' => is_array($code) ? $code[1] : ($haveDefaultSearchField),
            'afterSearchHandle' => $afterSearchHandle,
        ];

        return $this;
    }


    public function readonly(): static
    {
        return $this->setVAttrs(':disabled', 'true');
    }
}