<?php
/**
 * datetime: 2023/6/4 0:28
 **/

namespace Sc\Util\HtmlStructure\Form;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Col;

/**
 * 表单项目
 *
 * Class AbstractFormItem
 * @method $this setWhen(string $when) 何时展示 js 展示条件，可使用 when 方法代替
 * @method $this setHide(bool $where)  隐藏条件直接再php层面过滤
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/4
 */
abstract class AbstractFormItem
{
    use Col;

    protected ?\Closure $beforeRender = null;

    protected array $config = [];

    public function __call(string $name, mixed $value)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$value);
        }

        $type     = lcfirst(substr($name, 0, 3));
        $property = lcfirst(substr($name, 3));
        if ($type === 'set') {
            $this->setConfig($property, ...$value);
            return $this;
        }

        if (isset($this->{$property})) {
            return $this->{$property};
        }

        return $this->getConfig($property);
    }

    public function __get(string $name)
    {
        return $this->getConfig($name);
    }

    public function __set(string $name, $value): void
    {
        $this->setConfig($name, $value);
    }

    /**
     * @param \Closure|null $beforeRender 渲染之前处理的函数 参数就是渲染完成的 dom
     *                      参数： $Html AbstractHtmlElement
     * @return AbstractFormItem
     */
    public function beforeRender(?\Closure $beforeRender): AbstractFormItem
    {
        $this->beforeRender = $beforeRender;
        return $this;
    }

    /**
     * 何时展示 js 展示条件
     *
     * @param string ...$wheres
     *
     * @return AbstractFormItem
     */
    public function when(string ...$wheres): static
    {
        $where = implode(' ', $wheres);
        if (count($wheres) == 2 && !str_contains($where, '=')) {
            $where =  $wheres[0] . ' === ' . $wheres[1];
        }

        $this->setConfig('when', $where);

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    protected function setConfig(string $name, mixed $value): void
    {
        $this->config[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    protected function getConfig(string $name, mixed $default = null): mixed
    {
        return $this->config[$name] ?? $default;
    }

    /**
     * 设置只读
     *
     * @param string $when js 条件
     * @return $this
     */
    public function readonly(string $when = ''): static
    {
        if (method_exists($this, 'setVAttrs')) {
            $when
                ? $this->setVAttrs(":readonly", $when)
                : $this->setVAttrs("readonly");
        }

        return $this;
    }

    /**
     * 设置Label位置
     *
     * @param string $position
     * @return $this
     */
    public function labelPosition(#[ExpectedValues(['left', 'top', 'right', ''])] string $position): static
    {
        $this->setConfig('labelPosition', $position);
        return $this;
    }
}