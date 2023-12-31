<?php
/**
 * datetime: 2023/6/3 2:47
 **/

namespace Sc\Util\HtmlStructure\Form;

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
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTextThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemText
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
class FormItemText extends AbstractFormItem implements FormItemInterface
{
    use DefaultConstruct, DefaultValue, Options, Placeholder, LabelWidth, FormOrigin, Events, Attrs;

    protected string|JsFunc $autoCompleteHandle = '';
    protected \Stringable|string $autoCompleteFormat = '';

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemTextThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * 设置为自动完成
     *
     * @param string|array|JsFunc $complete 字符串：远程，数组: 搜索value [[value => 1]], JsFunc: 自定义搜索
     * @param \Stringable|string  $format  自定义模板, 例： <template #default="{ item }">{{item.value}}</template
     *
     * @return FormItemText
     */
    public function autoComplete(string|array|JsFunc $complete, \Stringable|string $format = ''): static
    {
        is_array($complete)
            ? $this->options($complete)
            : $this->autoCompleteHandle = $complete;

        $this->autoCompleteFormat = $format;

        return $this;
    }

    /**
     * 转为密码
     *
     * @return FormItemText
     */
    public function toPassword(): static
    {
        $this->setVAttrs('type', 'password');

        return $this;
    }
}