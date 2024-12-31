<?php
/**
 * datetime: 2023/5/15 0:31
 **/

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\VueComponentInterface;
use Sc\Util\HtmlStructure\Html\StaticResource;

/**
 * JS Vue3
 *
 * Class Vue
 *
 * @package Sc\Util\HtmlStructure\Html
 * @date    2023/5/15
 */
class Vue
{
    const VAR_NAME = 'VueApp';

    /**
     * 生命周期事件列表
     */
    public const EVENTS = [
        'beforeCreate',
        'created',
        'beforeMount',
        'mounted',
        'beforeUpdate',
        'updated',
        'activated',
        'deactivated',
        'beforeDestroy',
        'destroyed',
        'errorCaptured',
    ];
    /**
     * data
     *
     * @var array
     */
    private array $config;

    private array $data;

    private string $el;

    private array $use = [];
    /**
     * @var array|VueComponentInterface[]
     */
    private array $components = [];
    /**
     * @var array|null
     */
    private ?array $makeComponent = null;

    public function __construct()
    {
        $this->config = [];
        $this->data   = [];
        $this->el     = '#app';

        Html::js()->load(StaticResource::VUE);

        $this->config['methods'] = [];

        Html::html()->find($this->el)->setAttr('v-cloak');
        Html::css()->addCss('[v-cloak]{display: none}');
    }

    /**
     * 设置Data
     *
     * @param string $name
     * @param mixed  $value
     *
     * @date 2023/5/16
     */
    public function set(string $name, mixed $value): void
    {
        $value = (is_string($value) && str_starts_with($value, '@')) ? Grammar::mark(substr($value, 1)) : $value;

        $this->makeComponent
            ? $this->makeComponent['data'][$name] = $value
            : $this->data[$name] = $value;
    }

    /**
     * 绑定值，返回绑定变量
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string
     */
    public function bind(string $name, mixed $value): string
    {
        $this->set($name, $value);

        return $name;
    }

    /**
     * 引入模块
     *
     * @param string|array $mode
     *
     * @date 2023/5/25
     */
    public function use(string|array $mode): void
    {
        in_array($mode, $this->use) or $this->use[] = $mode;
    }

    /**
     * 获取data
     *
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     * @date 2023/5/16
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if ($this->makeComponent) {
            return $this->makeComponent['data'][$name] ?? $default;
        }

        return $this->data[$name] ?? $default;
    }

    /**
     * 存在dataKey
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasVar(string $name): bool
    {
        if ($this->makeComponent) {
            return isset($this->makeComponent['data'][$name]);
        }

        return isset($this->data[$name]);
    }

    /**
     * Vue methods 设置
     *
     * @param string       $name
     * @param array|JsFunc $params
     * @param string       $code
     *
     * @date 2023/5/19
     */
    public function addMethod(string $name, array|JsFunc $params = [], #[Language('JavaScript')] string $code = ''): void
    {
        $method = $params instanceof JsFunc ? $params->toCode() : JsFunc::anonymous($params, $code)->toCode();

        if ($this->makeComponent) {
            $this->makeComponent['methods'][$name] = $method;
            return;
        }
        $this->config['methods'][$name] = $method;
    }

    /**
     * 获取可用的method
     *
     * @param string $method
     *
     * @return string
     */
    public function getAvailableMethod(string $method = ''): string
    {
        if (!$method) {
            return $this->getAvailableMethod("cusMethod");
        }

        if ($this->existsMethod($method)) {
            return $this->getAvailableMethod($method . "_m_");
        }

        return $method;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAvailableDataName(string $name = ''): string
    {
        if (!$name) {
            return $this->getAvailableDataName("cusVar");
        }

        if ($this->existsData($name)) {
            return $this->getAvailableDataName($name . "_v");
        }

        return $name;
    }

    /**
     * 是否存在 method
     *
     * @param string $method
     *
     * @return bool
     */
    public function existsMethod(string $method): bool
    {
        if ($this->makeComponent) {
            return isset($this->makeComponent['methods'][$method]);
        }
        return isset($this->config['methods'][$method]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function existsData(string $name): bool
    {
        if ($this->makeComponent) {
            return isset($this->makeComponent['data'][$name]);
        }
        return isset($this->data[$name]);
    }

    /**
     * Vue 生命周期事件
     *
     * @param string $event
     * @param string $code
     *
     * @date 2023/5/19
     */
    public function event(#[ExpectedValues(self::EVENTS)] string $event, #[Language('JavaScript')] mixed $code = '', bool $isNextTick = false): void
    {
        $code = $isNextTick ? "this.\$nextTick(() => {\n{$code}\n})" : $code;

        if ($this->makeComponent) {
            $this->makeComponent[$event][] = $code;
            return;
        }

        if (empty($this->config[$event])) {
            $this->config[$event] = [];
        }

        $this->config[$event][] = $code;
    }

    /**
     * 属性监听
     *
     * @param string $name
     * @param JsFunc $handle
     * @param array  $options 多级监听 ['deep' => true]
     *
     * @date 2023/5/19
     */
    public function watch(string $name, JsFunc $handle, array $options = []): void
    {
        if ($this->makeComponent) {
            $this->makeComponent['watch'][$name] = array_merge([
                'handle' => $handle->toCode()
            ], $options);
            return;
        }

        if (empty($this->config->watch)) {
            $this->config['watch'] = [];
        }

        $this->config['watch'][$name] = array_merge([
            'handle' => $handle->toCode()
        ], $options);
    }

    /**
     * Vue 配置值设置
     *
     * @param string $option
     * @param mixed  $value
     *
     * @date 2023/5/19
     */
    public function config(string $option, mixed $value): void
    {
        if ($this->makeComponent) {
            $this->makeComponent[$option] = $value;
            return;
        }
        $this->config[$option] = $value;
    }

    /**
     * 获取配置
     *
     * @param string     $option
     * @param mixed|null $default
     *
     * @return mixed|null
     * @date 2023/5/25
     */
    public function getConfig(string $option, mixed $default = null): mixed
    {
        if ($this->makeComponent) {
            return $this->makeComponent[$option] ?? $default;
        }
        return $this->config[$option] ?? $default;
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * @return string
     * @date 2023/5/19
     */
    public function toCode(): string
    {
        Html::js()->defVar('VueInitData', $this->data);
        Html::js()->defVar("VueBaseData", new \stdClass());

        // 设置data
        $block = JsCode::create(Obj::use('VueBaseData')->set('data', JsFunc::anonymous([], "return VueInitData;")));
        // 生命周期事件处理
        foreach (self::EVENTS as $EVENT) {
            if (!empty($this->config[$EVENT])) {
                $block->then(Obj::use("VueBaseData")->set($EVENT, JsFunc::anonymous([], implode("\r\n", $this->config[$EVENT]))->toCode()));
            }
        }
        $block->then(Obj::use("VueBaseData")->set('methods', new \stdClass()));
        foreach ($this->config['methods'] as $method => $call) {
            $block->then(Obj::use("VueBaseData.methods")->set($method, $call));
        }

        $block->then(JsVar::assign('VueAppInit', JsFunc::call('Vue.createApp', '@VueBaseData')));
        foreach ($this->use as $use) {
            $block->then(JsFunc::call("VueAppInit.use", ...$use));
        }

        foreach ($this->components as $component) {
            $block->then($component->register('VueAppInit'));
        }

        // 加载饿了么图标
        $block->then(Html::js()->getUnitCodeBlock('elementPlusIconLoad'));

        // 重新赋值，保证在最后
        $block->then(JsVar::assign(self::VAR_NAME, JsFunc::call('VueAppInit.mount', $this->el)));

        Html::js()->defCodeBlock($block);

        return '';
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    /**
     * @param VueComponentInterface $components
     *
     * @return Vue
     */
    public function addComponents(VueComponentInterface $components): Vue
    {
        $this->components[$components->getName()] = $components;
        return $this;
    }

    public function startMakeTmpComponent(string $name): void
    {
        $this->makeComponent = [
            "template" => Grammar::mark("document.getElementById('vue--$name').innerHTML"),
        ];
    }

    public function getTmpComponent(): ?array
    {
        return $this->makeComponent;
    }

    public function endMakeTmpComponent(): void
    {
        $this->makeComponent = null;
    }

    public function setTmpComponentOnShowHandle(#[Language('JavaScript')] string $handle): static
    {
        if (!$this->makeComponent) {
            return $this;
        }

        $this->makeComponent['onShow'][] = $handle;
        return $this;
    }
}