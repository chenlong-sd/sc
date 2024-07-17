<?php

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSubmitThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemSubmit
 */
class FormItemSubmit extends AbstractFormItem implements FormItemInterface
{
    use FormOrigin, Events,LabelWidth;

    protected string $createUrl = '';
    protected string $updateUrl = '';
    protected string $resetHandle = '';
    protected string $submitHandle = '';
    protected string $successCloseCode = '';
    protected string $fail = '';
    protected string $success = '';
    protected string $successTipCode = 'this.$message.success("成功")';
    protected array $closePage = ['page' => 'current', 'theme' => 'ElementUI'];

    /**
     * @param string $submitText
     * @param string $resetText 为空时则隐藏
     */
    public function __construct(
        protected string $submitText = '提交',
        protected string $resetText = '重置'
    ){
        $this->successTipCode = JsService::message("成功");
    }

    public function render(string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(FormItemSubmitThemeInterface::class, $theme)->render($this);
    }

    public function setSubmit(#[Language('JavaScript')] string $jsCode): static
    {
        $this->submitHandle = $jsCode;

        return $this;
    }

    public function setReset(#[Language('JavaScript')] string $jsCode): static
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
     * @param string      $page
     * @param string|null $theme
     *
     * @return $this
     */
    public function successClose(#[ExpectedValues([
        'current', // 当前页面
        'parent' , // 父级页面
    ])] ?string $page, #[ExpectedValues([...Theme::AVAILABLE_THEME, null])] ?string $theme = null): static
    {
        if ($theme === null) {
            $theme = $page == 'parent' ? 'Layui' : 'ElementUI';
        }

        $this->closePage = compact('page', 'theme');
        if ($page == 'parent') {
            $this->successTipCode = JsService::message("成功")->toParent();
        }

        return $this;
    }

    public function success(#[Language('JavaScript')] string $code, bool $strict = false): static
    {
        $this->success = $strict ? "@strict " . $code : $code;

        return $this;
    }

    public function fail(#[Language('JavaScript')] string $code): static
    {
        $this->fail = $code;

        return $this;
    }
}