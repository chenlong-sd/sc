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
    protected function __construct(
        private readonly string $name,
        private AbstractHtmlElement|string $content = '',
        private array $config = [],
    )
    {
        Html::js()->vue->startMakeTmpComponent($this->name);
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
     * @param AbstractHtmlElement|string $content
     *
     * @return $this
     */
    public function setContent(AbstractHtmlElement|string $content): static
    {
        $this->content = El::get($content);

        $code = JsCode::create('// nothing');
        if ($this->content->getLabel() === 'el-form'){
            // 判断组件是否是一个表单，如果是表单看是否有传输默认数据，如果有则设置对应的提交地址

            $vModel = $this->content->getAttr(':model');

            $code->then(Js::let('row', '@data'));
            $code->then(Js::assign("this.{$vModel}Url", "@typeof row != 'undefined' && row.hasOwnProperty('id') && row.id ? this.{$vModel}UpdateUrl : this.{$vModel}CreateUrl"));

            if ($this->content->hasAttr("v-loading")) {
                $code->then(
                    Js::if("this['{$vModel}GetDefaultData'] !== undefined")
                        ->then("this['{$vModel}GetDefaultData'](row.id)")
                        ->else("this.{$vModel}Default(row)")
                );
            }else{
                $code->then("this.{$vModel}Default(row)");
            }
        }

        $this->config = Html::js()->vue->getTmpComponent();

        if (!empty($this->config['onShow'])) {
            $code->then(...$this->config['onShow']);
        }

        if (!empty(Html::js()->vue->getConfig('methods')['init'])) {
            $code->then("this.init(row)");
        }

        if (empty(Html::js()->vue->getConfig('methods')['onShow'])) {
            Html::js()->vue->addMethod('onShow', ['data'], $code);
        }

        $this->config = Html::js()->vue->getTmpComponent();

        Html::js()->vue->endMakeTmpComponent();

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function register(string $registerVar): string
    {
        Html::html()->find('body')->after(
            El::double('script')->setAttrs([
                'id'   => "vue--{$this->getName()}",
                'type' => 'text/x-template'
            ])->append($this->content)
        );

        $this->config['data'] = JsFunc::anonymous()->code("return " . json_encode($this->config['data'] ?? new \stdClass(), JSON_PRETTY_PRINT));

        // 生命周期事件处理
        foreach (Vue::EVENTS as $EVENT) {
            if (!empty($this->config[$EVENT])) {
                $this->config[$EVENT] = JsFunc::anonymous([], implode("\r\n", $this->config[$EVENT]))->toCode();
            }
        }

        unset($this->config['onShow']);
        return JsFunc::call("$registerVar.component", $this->getName(), $this->config)->toCode();
    }
}