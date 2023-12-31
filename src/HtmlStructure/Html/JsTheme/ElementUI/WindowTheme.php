<?php
/**
 * datetime: 2023/6/2 0:53
 **/

namespace Sc\Util\HtmlStructure\Html\JsTheme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Html\Js\Window;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\WindowThemeInterface;

class WindowTheme implements WindowThemeInterface
{

    public function render(Window $window): string
    {
        mt_srand();
        $vModel = "VueWindow" . mt_rand(1, 999);

        $attrs    = array_map(fn($v) => $v instanceof \Stringable ? (string)$v : $v, $window->getConfig());
        $isIframe = $window->getUrl();

        $attrs = array_merge([
            'destroy-on-close' => $isIframe ? '' : null,
            'title'            => $window->getTitle(),
            'v-model'          => $vModel,
        ], $attrs);

        $template = El::double('el-dialog')->setAttrs($attrs);
        $code = JsCode::create('// 打开弹窗');

        if ($isIframe) {
            // iframe
            $this->iframeHandle($vModel, $template, $window, $code);
        } else if ($window->getComponent()) {
            $this->componentHandle($template, $window, $code);
        } else {
            $elements = El::get($window->getContent());
            // 常规内容
            if ($elements->getParent()?->getLabel() === 'el-dialog') {
                // 改内容已有父级dialog,表明已被使用，这里直接调用打开的方式就可以了
                $vModel = $elements->getParent()->getAttr('v-model');
                $dialog = true;
            }
            $this->normalHandle($elements, $template, $code);
        }

        if (empty($dialog)) {
            Html::html()->find('#app')->append($template);
        }

        Html::js()->vue->set($vModel, false);
        Html::js()->vue->set('windowHeight', "@windowHeight");

        $this->registerCloseWindow();

        return $code->then(JsVar::assign("this.$vModel", true));
    }

    /**
     * @param string      $vModel
     * @param DoubleLabel $template
     * @param Window      $window
     * @param JsCode      $code
     *
     * @return void
     */
    private function iframeHandle(string $vModel, DoubleLabel $template, Window $window, JsCode $code): void
    {
        $fullScreen = El::double('el-link')->setAttr('style', 'float: right; padding: 0 15px;position: relative;top: 5px;');
        $fullScreen->setAttrs([':underline' => 'false', '@click' => "{$vModel}FullScreenChange"]);
        $fullScreen->append(El::double('el-icon')->setAttr(':size', 15)->append(El::single('Full-Screen')));

        $config = $window->getConfig();
        $height = rtrim($config['height'] ?? "windowHeight", 'px');

        $template->setAttrs(array_merge([
            'width'        => '90%',
            'align-center' => '',
            'ref'          => $vModel,
            'id'           => $vModel,
            ":fullscreen"  => "{$vModel}FullScreen",
        ], $config))->append(
            El::double('iframe')->setAttrs([
                ':test'  => 'windowHeight',
                ':style' => "{width:'100%',maxHeight:windowHeight + 'px', height:$height + 'px' ,border:'none'}",
                ':src'   => "{$vModel}IframeUrl",
            ])
        )->append(
            El::double('template')->setAttr('#header', '{ close, titleId, titleClass }')
                ->append(El::double('span')->append($window->getTitle()))
                ->append($fullScreen)
        );

        Html::css()->addCss("#$vModel .el-dialog__body{ padding-top: 10px}");
        Html::js()->vue->set($vModel . 'FullScreen', false);
        Html::js()->vue->addMethod("{$vModel}FullScreenChange", [], JsCode::create("this.{$vModel}FullScreen = !this.{$vModel}FullScreen")
            ->thenIf("this.{$vModel}FullScreen", 'this.windowHeight = window.innerHeight - 60', 'this.windowHeight = windowHeight')
        );

        // 设置iframe地址
        Html::js()->vue->set("{$vModel}Query", $window->getQuery());
        Html::js()->vue->addMethod("{$vModel}IframeBaseUrl", [], "return '{$window->getUrl()}';");
        $code->then(<<<JS
                let parsedUrl = new URL(this['{$vModel}IframeBaseUrl']());

                parsedUrl.searchParams.forEach((v, k, p) => {
                    if (/^@/.test(v) && row.hasOwnProperty(v.substring(1))){
                        p.set(k, row[v.substring(1)]);
                    }
                })
                
                for(const key in this['{$vModel}Query']){
                    let value = this['{$vModel}Query'][key];
                    if (/^@/.test(value) && row.hasOwnProperty(value.substring(1))){
                        parsedUrl.searchParams.set(key, row[value.substring(1)]);
                        continue;
                    }
                    parsedUrl.searchParams.set(key, value);
                }
                
                this['{$vModel}IframeUrl'] = parsedUrl.href;
            JS);
    }

    /**
     * @param AbstractHtmlElement|string|null $elements
     * @param DoubleLabel                     $template
     * @param JsCode                          $code
     *
     * @return void
     */
    private function normalHandle(AbstractHtmlElement|string|null $elements, DoubleLabel $template, JsCode $code): void
    {
        if (method_exists($elements, 'getLabel') && $elements->getLabel() === 'el-form') {
            $vModel = $elements->getAttr('v-model');
            if ($elements->hasAttr("v-loading")) {
                // 说明需要请求后台获取默认数据。
                // 实际可能在创建数据的时候也会使用这个，所以再判断一下 row.id 值是否为真
                $code->then(JsCode::if('row !== undefined && row.id', "this['{$vModel}GetDefaultData'](row.id)", "this.{$vModel}Default(row)"));
            }else{
                $code->then("this.{$vModel}Default(row)");
            }

            $submit = $elements->find('[submit-sign]');
            $code->then(JsCode::if('row !== undefined && row.id',
                JsVar::assign("this.{$vModel}Url", "@this.{$vModel}UpdateUrl"),
                JsVar::assign("this.{$vModel}Url", "@this.{$vModel}CreateUrl"))
            );
            if ($submit) {
                $submit->remove();

                $template->append($elements)->append(
                    El::double('template')->setAttr('#footer')->append(...$submit->getChildren())
                );
            }
        }else{
            if ($elements->getParent()?->getLabel() !== 'el-dialog') {
                $template->append($elements);
            }
        }
    }

    /**
     * 关闭窗口
     *
     * @return void
     */
    private function registerCloseWindow(): void
    {
        Html::js()->vue->addMethod('closeWindow', ['windowSign'], <<<JS
            if(windowSign){
                this[windowSign] = false;
                return;
            }
            
            for (const key of Object.keys(this.\$data)) {
                if (/^VueWindow\d+$/.test(key)){
                    this[key] = false;
                }
            }
        JS);
    }

    /**
     * 组件
     *
     * @param DoubleLabel $template
     * @param Window      $window
     * @param JsCode      $code
     *
     * @return void
     */
    private function componentHandle(DoubleLabel $template, Window $window, JsCode $code): void
    {
        $vueComponent = $window->getComponent();

        $showVar = $template->getAttr('v-model') . 'Children';
        $template->append(
            El::double($vueComponent->getName())
                ->setAttrs([
                    'ref' => $vueComponent->getName(),
//                    'v-if' => $showVar
                ])
        );

        $code->then(JsVar::def('f',
            JsFunc::call('setTimeout', JsFunc::arrow()->code(
                JsCode::if("this.\$refs['{$vueComponent->getName()}'] !== undefined",
                    JsCode::create("this.\$refs['{$vueComponent->getName()}'].onShow(row)")
                    ->then("clearInterval(f)")
                )
            )
        )));
//        Html::js()->vue->set($showVar, false);
        Html::js()->vue->addComponents($vueComponent);
    }
}