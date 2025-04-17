<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\Tool\Url;

/**
 * Class Axios
 */
class Axios
{
    private JsFunc $thenCallable;
    private JsFunc $catchCallable;
    private JsFunc $finallyCallable;
    private JsCode $success;
    private JsCode $fail;
    private ?string $loadingText = null;
    private ?string $confirmMessage = null;
    private ?string $promptMessage = null;

    private ?Window $mountIframeInfo = null;

    public function __construct(private array $options)
    {
        $this->success = Js::code('// success code');
        $this->fail    = Js::code('// fail code');

        $this->then(JsFunc::arrow(["{ data }"], "// nothing"));
        $this->catch(JsFunc::arrow(["error"], JsService::message(Grammar::mark('error'), 'error')));
        $this->finally(JsFunc::arrow()->code("// nothing"));

        Html::js()->load(StaticResource::AXIOS);
    }

    public static function create(array $options): Axios
    {
        return new self(self::dataHandle($options));
    }

    public static function post(string|\Stringable $url = '', mixed $data = []): Axios
    {
        return new self([
            'url'    => $url,
            'method' => 'post',
            'data'   => self::dataHandle($data)
        ]);
    }

    /**
     * @param mixed $data
     *
     * @return array|string
     */
    private static function dataHandle(mixed $data = []): array|string
    {
        if (Grammar::check($data)) {
            return Grammar::mark(substr($data, 1));
        }

        if (is_string($data)) {
            return $data;
        }

        return array_map(fn($v) => Grammar::check($v) ? Grammar::mark(substr($v, 1)) : $v, $data);
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function data(array $data): static
    {
        $this->options['data'] = self::dataHandle($data);

        return $this;
    }

    /**
     * 挂载在iframe的信息， iframe只支持layui
     *
     * @param Window $window
     *
     * @return Axios
     */
    public function mountIframe(Window $window): static
    {
        if (empty($window->getConfig()['width'])) {
            $window->setConfig(['width' => '1000px']);
        }
        $this->mountIframeInfo = $window;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function options(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function params(array $params): static
    {
        $this->options['params'] = self::dataHandle($params);

        return $this;
    }

    public static function get(string|\Stringable $url = '', mixed $query = []): Axios
    {
        return new self([
            'url'    => $url,
            'method' => 'get',
            'params' => self::dataHandle($query) ?: []
        ]);
    }

    /**
     * 请求成功之后
     *
     * @param JsFunc $callable
     *
     * @return $this
     */
    public function then(JsFunc $callable): static
    {
        $this->thenCallable = $callable;

        return $this;
    }

    /**
     * 请求异常之后
     *
     * @param JsFunc $callable
     *
     * @return $this
     */
    public function catch(JsFunc $callable): static
    {
        $this->catchCallable = $callable;

        return $this;
    }

    /**
     * 请求完成之后
     *
     * @param JsFunc $callable
     *
     * @return $this
     */
    public function finally(JsFunc $callable): static
    {
        $this->finallyCallable = $callable;

        return $this;
    }

    /**
     * 请求确认消息
     *
     * @param string $message
     *
     * @return $this
     * @date 2023/6/1
     */
    public function confirmMessage(string $message): static
    {
        $this->confirmMessage = $message;

        return $this;
    }

    public function toCode(): string
    {
        empty($this->options['url']) || $this->options['url'] = (string)$this->options['url'];

        $code = JsCode::create('// 请求开始');
        if ($this->loadingText) {
            $code->then(Js::let('load', JsService::loading($this->loadingText)));
            $this->finallyCallable->appendCode('load.close()');
        }

        if ($this->thenCallable->code === '// nothing') {
            if ($this->mountIframeInfo) {
                $this->success->then("layer.close(index)");
            }

            $this->thenCallable->code(Js::if('data.code === 200', $this->success, $this->fail));
        }

        $code->then(
            JsFunc::call('axios', $this->options)
                ->call('then', $this->thenCallable)
                ->call('catch', $this->catchCallable)
                ->call('finally', $this->finallyCallable)
        );

        if ($this->confirmMessage) {
            $code = JsService::confirm([
                'message' => Js::grammar("`$this->confirmMessage`"),
                'then'    => $code,
                'type'    => 'warning'
            ]);
        } elseif ($this->promptMessage) {
            $code = JsService::prompt([
                'message' => Js::grammar("`$this->promptMessage`"),
                'then'    => $code,
                'type'    => 'text'
            ]);
        }


        return $this->mountIframeHandle($code->toCode());
    }

    private function mountIframeHandle(string $code): string
    {
        if (empty($this->mountIframeInfo)) {
            return $code;
        }

        /**
         * 重新设置一下elementUI的层级，避免层级不够展示在下面了
         */
        Html::css()->addCss('.el-message, .is-message-box {
          z-index: 999999991 !important; /* 设置你想要的z-index值 */
        }');
        if (!Html::html()->find('el-config-provider')) {
            Html::html()->prepend(El::double('el-config-provider')->setAttr(':z-index', '99999999'));
        }

        $this->mountIframeInfo
            ->setConfig('btn', [$this->mountIframeInfo->getTitle()])
            ->setConfig('btnAlign', 'c')
            ->setConfig('yes', JsFunc::arrow(['index'])->code($code));

        return $this->mountIframeInfo->toCode();
    }


    public function __toString(): string
    {
        return $this->toCode();
    }

    /**
     * @param string|null $loadingText
     *
     * @return Axios
     */
    public function addLoading(?string $loadingText = "请稍后..."): Axios
    {
        $this->loadingText = $loadingText;

        return $this;
    }

    /**
     * @param mixed $success
     *
     * @return $this
     */
    public function success(#[Language('JavaScript')] mixed $success): Axios
    {
        $this->success->then($success);

        return $this;
    }

    /**
     * @param mixed $fail
     *
     * @return $this
     */
    public function fail(#[Language('JavaScript')] mixed $fail): Axios
    {
        $this->fail->then($fail);

        return $this;
    }

    public function prompt(string $message, string $inputName = 'prompt_value'): static
    {
        $this->promptMessage = $message;

        if (strtoupper($this->options['method']) === "POST") {
            $this->options['data'][$inputName] = Grammar::mark("value.value");
        }else{
            $this->options['params'][$inputName] = Grammar::mark("value.value");
        }

        return $this;
    }
}