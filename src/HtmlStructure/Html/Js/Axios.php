<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\StaticResource;

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

    public function __construct(private array $options)
    {
        $this->success = JsCode::create('// success code');
        $this->fail    = JsCode::create('// fail code');

        $this->then(JsFunc::arrow(["{ data }"], "// nothing"));
        $this->catch(JsFunc::arrow(["error"], JsService::message(Grammar::mark('error'), 'error')));
        $this->finally(JsFunc::arrow()->code("// nothing"));

        Html::js()->load(StaticResource::AXIOS);
    }

    public static function create(array $options): Axios
    {
        return new self($options);
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
            $code->then(JsVar::def('load', JsService::loading($this->loadingText)));
            $this->finallyCallable->appendCode('load.close()');
        }

        if ($this->thenCallable->code === '// nothing') {
            $this->thenCallable->code(JsCode::if('data.code === 200', $this->success, $this->fail));
        }

        $code->then(
            JsFunc::call('axios', $this->options)
                ->call('then', $this->thenCallable)
                ->call('catch', $this->catchCallable)
                ->call('finally', $this->finallyCallable)
        );

        return ($this->confirmMessage
            ? JsService::confirm([
                'message' => Grammar::mark("`$this->confirmMessage`"),
                'then'    => $code,
                'type'    => 'warning'
            ])
            : $code)->toCode();
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
}