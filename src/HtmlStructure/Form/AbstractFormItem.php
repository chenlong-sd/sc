<?php
/**
 * datetime: 2023/6/4 0:28
 **/

namespace Sc\Util\HtmlStructure\Form;

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

    protected array $setting = [];

    public function __call(string $name, mixed $value)
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        $type     = lcfirst(substr($name, 0, 3));
        $property = lcfirst(substr($name, 3));
        if ($type === 'set') {
            $this->setter($property, current($value));
            return $this;
        }

        if (isset($this->{$property})) {
            return $this->{$property};
        }

        return $this->getter($property);
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

        $this->setter('when', $where);

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    private function setter(string $name, mixed $value): void
    {
        $this->setting[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getter(string $name): mixed
    {
        return $this->setting[$name] ?? null;
    }

    public function readonly(): static
    {
        if (method_exists($this, 'setVAttrs')) {
            $this->setVAttrs("readonly");
        }

        return $this;
    }
}