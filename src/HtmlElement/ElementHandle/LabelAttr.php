<?php
/**
 * datetime: 2023/4/13 2:08
 **/

namespace Sc\Util\HtmlElement\ElementHandle;

use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\El;

/**
 * 标签属性
 *
 * Trait LabelAttr
 *
 * @package Sc\Util\Element
 * @date    2023/4/13
 */
trait LabelAttr
{
   public function __construct(
       private string $label
   ) { }

    /**
     * 属性集合
     *
     * @var array
     */
    private array $attrs = [];

    /**
     * 设置属性
     *
     * @param string $attr  属性名
     * @param mixed  $value 属性值, 值为 null 则删除属性, {# attr } 可替换原有的属性
     *
     * @return $this
     * @date 2023/4/13
     */
    public function setAttr(string $attr, string|int|null $value = ''): static
    {
        if ($value === null) {
            $this->removeAttr($attr);
        }else{
            $this->attrs[$attr] = preg_replace_callback("/\{#([\w\-_@:|]+)}/", function ($match){
                $matchAttr = explode('|', $match[1]);
                return $this->getAttr($matchAttr[0]) !== null ? $this->getAttr($matchAttr[0]) : ($matchAttr[1] ?? null);
            }, $value);
        }

        return $this;
    }

    public function setStyle(#[Language("CSS")] $style): static
    {
        $this->setAttr('style', trim($style, '{}'));

        return $this;
    }

    public function appendStyle(#[Language("CSS")] $style): static
    {
        $this->appendAttr('style',  trim($style, '{}'));

        return $this;
    }

    /**
     * 设置属性
     *
     * @param string $attrStr  aa="asd" ass="asdsad" 等
     *
     * @return $this
     */
    public function setAttrFromStr(string $attrStr): static
    {
        $attr = El::getAttrFromStr($attrStr);

        return $this->setAttrs($attr);
    }

    /**
     * 如果不存在，则设置属性
     *
     * @param string     $attr
     * @param string|int $value
     *
     * @return $this
     * @date 2023/4/15
     */
    public function setAttrIfNotExist(string $attr, string|int $value = ''): static
    {
        if ($this->hasAttr($attr)) {
            return $this;
        }

        return $this->setAttr($attr, $value);
    }

    /**
     * 批量设置属性
     *
     * @param array $attrs
     *
     * @return $this
     * @date 2023/4/13
     */
    public function setAttrs(array $attrs): static
    {
        foreach ($attrs as $attr => $value) {
            $this->setAttr($attr, $value);
        }

        return $this;
    }

    /**
     * 获取属性
     *
     * @param string          $attr     属性名
     * @param string|int|null $default  默认值
     *
     * @return mixed|string
     * @date 2023/4/13
     */
    public function getAttr(string $attr, string|int $default = null): mixed
    {
        return $this->attrs[$attr] ?? $default;
    }

    /**
     * 获取所有属性
     *
     * @return array
     * @date 2023/5/29
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * 追加属性值
     *
     * @date 2023/4/13
     *
     * @param string     $attr
     * @param string|int $value
     *
     * @return $this
     */
    public function appendAttr(string $attr, string|int $value): static
    {
        if (!$this->hasAttr($attr)) {
            $this->setAttr($attr);
        }

        $this->attrs[$attr] .= $value;

        return $this;
    }

    /**
     * 删除属性值
     *
     * @param string|null $attr
     *
     * @return $this
     * @date 2023/4/13
     */
    public function removeAttr(string $attr = null): static
    {
        if ($attr === null) {
            $this->attrs = [];
        }else{
            unset($this->attrs[$attr]);
        }

        return $this;
    }

    /**
     * 存在属性
     *
     * @param string $attr
     *
     * @return bool
     * @date 2023/4/13
     */
    public function hasAttr(string $attr): bool
    {
        return isset($this->attrs[$attr]);
    }

    /**
     * 添加class
     *
     * @param string $class
     *
     * @return $this
     * @date 2023/4/13
     */
    public function addClass(string $class): static
    {
        return  $this->appendAttr('class', " $class");
    }

    /**
     * 是否存在class
     *
     * @param string $class
     *
     * @return bool
     * @date 2023/4/13
     */
    public function hasClass(string $class): bool
    {
        $currentClassArr = $this->getClassArr();

        return in_array($class, $currentClassArr);
    }

    /**
     * 删除class
     *
     * @param string $class
     *
     * @return $this
     * @date 2023/4/13
     */
    public function removeClass(string $class): static
    {
        $currentClassArr = $this->getClassArr();

        $updateClass = array_diff($currentClassArr, [$class]);

        return $this->setAttr('class', implode(' ', $updateClass));
    }

    /**
     * @param string $label
     *
     * @return LabelAttr
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * 设置元素ID
     *
     * @param string $id
     *
     * @return $this
     * @date 2023/4/15
     */
    public function setId(string $id): static
    {
        return $this->setAttr('id', $id);
    }

    /**
     * 获取ID
     *
     * @return mixed|string
     * @date 2023/4/15
     */
    public function getId(): mixed
    {
        return $this->getAttr('id');
    }

    /**
     * @return string[]
     * @date 2023/4/15
     */
    protected function getClassArr(): array
    {
        return array_filter(explode(' ', $this->getAttr('class', '')));
    }

    /**
     * 属性转字符串
     *
     * @return string
     */
    private function attrToString(): string
    {
        if (!$this->attrs) {
            return "";
        }

        $attrStr = [''];
        foreach ($this->attrs as $attr => $value) {
            $attrStr[] = $value === "" || $value === null ? $attr : sprintf('%s="%s"', $attr, $value);
        }
        return implode(' ', $attrStr);
    }
}