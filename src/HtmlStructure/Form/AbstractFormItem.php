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
 * @method $this setWhen(string $when) 何时展示 js 展示条件
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
     * @param AbstractHtmlElement $el
     *
     * @return AbstractHtmlElement
     * @date 2023/6/14
     */
    protected function ExecuteBeforeRendering(AbstractHtmlElement $el): AbstractHtmlElement
    {
        if (property_exists($this, 'attrs') && $this->attrs) {
            $el->find('[v-model]')?->setAttrs($this->attrs);
        }

        if ($this->beforeRender) {
            call_user_func($this->beforeRender, $el);
        }

        return $el;
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
}