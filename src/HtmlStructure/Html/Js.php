<?php
/**
 * datetime: 2023/5/15 0:25
 **/

namespace Sc\Util\HtmlStructure\Html;


use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\Layui;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Html\Js\Vue;

/**
 * Html页面的js
 *
 * Class Js
 *
 * @property Vue $vue
 * @property Layui $layui
 *
 * @package Sc\Util\HtmlStructure\Html
 * @date    2023/5/15
 */
class Js
{
    /**
     * 变量集合
     *
     * @var \stdClass
     */
    private \stdClass $variables;

    /**
     * 函数集合
     *
     * @var \stdClass
     */
    private \stdClass $functions;

    /**
     * 代码块
     *
     * @var array
     */
    private array $codeBlock = [];

    /**
     * 要加载的js
     *
     * @var array
     */
    private array $loadJs = [];
    private array $unitCodeBlock = [];

    public function __construct()
    {
        $this->variables = new \stdClass();
        $this->functions = new \stdClass();

        $this->defVar("windowHeight", '@window.innerHeight - 200');
    }

    /**
     * 加载js
     *
     * @param string|array $jsPath
     *
     * @date 2023/5/25
     */
    public function load(string|array $jsPath): void
    {
        in_array($jsPath, $this->loadJs) or $this->loadJs[] = $jsPath;
    }

    /**
     * 定义变量
     *
     * @param string $name
     * @param mixed  $value
     *
     * @date 2023/5/16
     */
    public function defVar(string $name, mixed $value): void
    {
        $this->variables->{$name} = JsVar::def($name, $value, 'var');
    }

    /**
     * 获取变量
     *
     * @param string $name
     * @param        $default
     *
     * @return mixed
     */
    public function getVar(string $name, $default): mixed
    {
        return $this->variables->{$name}?->value ?? $default;
    }

    /**
     * 定义函数
     *
     * @param string $name
     * @param array  $params
     * @param string $code
     *
     * @date 2023/5/16
     */
    public function defFunc(string $name, array $params, #[Language('JavaScript')] string $code): void
    {
        $this->functions->{$name} = JsFunc::def($name, $params, $code);
    }

    /**
     * 定义代码块
     *
     * @param string|\Stringable $code
     *
     * @date 2023/5/16
     */
    public function defCodeBlock(#[Language('JavaScript')] string|\Stringable $code): void
    {
        $this->codeBlock[] = (string)$code;
    }

    public function defUniqueCodeBlock(string $key, #[Language('JavaScript')] string|\Stringable $code): void
    {
        $this->unitCodeBlock[$key] = (string)$code;
    }

    /**
     * @param string $name
     *
     * @return null
     * @date 2023/5/15
     */
    public function __get(string $name)
    {
        $value = $this->variables->{$name} ?? null;

        if ($name === 'vue') {
            if (empty($this->variables->{Vue::VAR_NAME})) {
                $this->defVar(Vue::VAR_NAME, new Vue());
            }
            $value = $this->variables->{Vue::VAR_NAME}->value;
        }

        return match ($name) {
            'layui' => new Layui(),
            default => $value
        };
    }

    public function __set(string $name, mixed $value)
    {
        $this->defVar($name, $value);
    }


    public function __toString(): string
    {
        return $this->toCode();
    }

    /**
     * @return string
     * @date 2023/5/19
     */
    public function toCode(): string
    {
        $code = [];
        foreach ($this->variables as $variable) {
            $code[] = sprintf("\r\n%s", $variable->toCode());
        }

        foreach ($this->functions as $function) {
            $code[] = sprintf("\r\n%s", $function->toCode());
        }

        $code[] = implode(";\r\n", $this->codeBlock);

        $this->loadJs();

        return Grammar::extract(implode("\r\n", $code));
    }

    private function loadJs(): void
    {
        $head = Html::html()->find('head');
        foreach ($this->loadJs as $jsPath) {
            $head->append(El::double('script')->setAttr('src', $jsPath));
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getUnitCodeBlock(string $key): string
    {
        return $this->unitCodeBlock[$key] ?? '';
    }
}

