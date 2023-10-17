<?php

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSubmitThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemSubmit
 */
class FormItemSubmit extends AbstractFormItem implements FormItemInterface
{
    use FormOrigin, Events;

    protected string $createUrl = '';
    protected string $updateUrl = '';
    protected string $resetHandle = '';
    protected string $submitHandle = '';
    protected string $successCloseCode = '';
    protected string $fail = '';
    protected string $success = '';
    protected string $successTipCode = 'this.$message.success("成功")';

    /**
     * @param string $submitText
     * @param string $resetText 为空时则隐藏
     */
    public function __construct(protected string $submitText = '提交', protected string $resetText = '重置'){}

    public function render(string $theme = null): AbstractHtmlElement
    {
        $el = Theme::getRender(FormItemSubmitThemeInterface::class, $theme)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    public function setSubmit(string $jsCode): static
    {
        $this->submitHandle = $jsCode;

        return $this;
    }

    public function setReset(string $jsCode): static
    {
        $this->resetHandle = $jsCode;

        return $this;
    }

    public function submitUrl(string $createUrl, string $updateUrl = null): static
    {
        $this->createUrl = $createUrl;
        $this->updateUrl = $updateUrl ?: $createUrl;

        return $this;
    }

    /**
     * 成功关闭
     *
     * @param string $page
     *
     * @return $this
     */
    public function successClose(#[ExpectedValues([
        'current', // 当前页面
        'parent' , // 父级页面
    ])] string $page): static
    {
        if ($page === 'current') {
            $this->successCloseCode = "VueApp.closeWindow()";
        }else{
            $this->successCloseCode = "parent.VueApp.closeWindow()";
            $this->successTipCode = 'parent.VueApp.$message.success("成功")';
        }

        return $this;
    }

    public function success(string $code, bool $strict = false): static
    {
        $this->success = $strict ? "@strict " . $code : $code;

        return $this;
    }

    public function fail(string $code): static
    {
        $this->fail = $code;

        return $this;
    }
}