<?php

namespace Sc\Util\HtmlStructure\Html\Js\VueComponents;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Html\Js\Vue;

/**
 * 临时组件
 *
 * Class Temporary
 */
class Temporary implements VueComponentInterface
{
    private Vue $vue;
    private array $onShow = [];
    private string|AbstractHtmlElement $template = '';

    protected function __construct(private readonly string $name)
    {
        $this->vue = new Vue(componentName: $this->name);
        Html::js()->setVue($this->vue);
    }

    /**
     * @param string $name
     *
     * @return Temporary
     */
    public static function create(string $name): Temporary
    {
        return new self("sc-vue-" . $name);
    }

    /**
     * @param AbstractHtmlElement|string $template
     *
     * @return $this
     */
    public function setContent(AbstractHtmlElement|string $template): static
    {
        return $this->setTemplate($template);
    }

    /**
     * @param AbstractHtmlElement|string $template
     *
     * @return $this
     */
    public function setTemplate(AbstractHtmlElement|string $template): static
    {
        $this->template = El::get($template);

        $code = JsCode::create('// nothing');
        if ($this->template->getLabel() === 'el-form'){
            // 判断组件是否是一个表单，如果是表单看是否有传输默认数据，如果有则设置对应的提交地址

            $vModel = $this->template->getAttr(':model');

            $code->then(Js::let('row', '@data'));
            $code->then(Js::assign("this.{$vModel}Url", "@typeof row != 'undefined' && row.hasOwnProperty('id') && row.id ? this.{$vModel}UpdateUrl : this.{$vModel}CreateUrl"));

            if ($this->template->hasAttr("v-loading")) {
                $code->then(
                    Js::if("this['{$vModel}GetDefaultData'] !== undefined")
                        ->then("this['{$vModel}GetDefaultData'](row.id)")
                        ->else("this.{$vModel}Default(row)")
                );
            }else{
                $code->then("this.{$vModel}Default(row)");
            }
        }

        if ($this->vue->hasMethod('init')) {
            $code->then("this.init(row)");
        }

        $this->addOnShow($code);

        Html::js()->resetVue();

        return $this;
    }

    /**
     * 添加 onShow 时的代码
     *
     * @param string $onShow
     *
     * @return $this
     */
    public function addOnShow(string $onShow): static
    {
        $this->onShow[] = $onShow;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function register(string $registerVar): string
    {
        Html::html()->find('body')->prepend(
            h('script', [
                'id'   => "vue--{$this->getName()}",
                'type' => 'text/x-template'
            ])->append($this->template),
        );

        $this->vue->addMethod('onShow', ['data'], implode("\r\n", $this->onShow));

        $this->vue->config('template', "@document.getElementById('vue--{$this->getName()}').innerHTML");
        return JsFunc::call("$registerVar.component", $this->getName(), $this->vue->getMakeConfig())->toCode();
    }

    public function getTemplate(): AbstractHtmlElement|string
    {
        return $this->template;
    }

    public function getVue(): Vue
    {
        return $this->vue;
    }
}