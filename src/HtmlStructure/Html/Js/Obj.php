<?php

namespace Sc\Util\HtmlStructure\Html\Js;

/**
 * Class Obj
 */
class Obj
{
    public function __construct(private string $obj){}

    public static function use(string $obj): Obj
    {
        return new self($obj);
    }

    private function then(string $then): void
    {
        $this->obj .= '.' . $then;
    }

    /**
     * 调用方法
     *
     * @param string      $name
     * @param             ...$params
     *
     * @return $this
     */
    public function call(string $name, ...$params): static
    {
        $this->then(JsFunc::call($name, ...$params));

        return $this;
    }

    /**
     * 获取属性 例:.attr
     *
     * @param string $name
     *
     * @return $this
     */
    public function get(string $name): static
    {
        $this->then($name);

        return $this;
    }

    /**
     * 使用 [index] 方式 获取属性
     *
     * @param string|int $index
     *
     * @return $this
     */
    public function index(string|int $index): static
    {
        if (str_starts_with($index, '@')) {
            $this->obj .= sprintf('[%s]', substr($index, 1));
        } elseif (is_int($index)) {
            $this->obj .= sprintf('[%d]', $index);
        } else {
            $this->obj .= sprintf('["%s"]', $index);
        }

        return $this;
    }

    /**
     * 设置属性
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $name, mixed $value): static
    {
        $this->then($name);

        $this->obj = JsVar::assign($this->obj, $value);

        return $this;
    }

    public function toCode(): string
    {
        return Grammar::mark($this->obj);
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}