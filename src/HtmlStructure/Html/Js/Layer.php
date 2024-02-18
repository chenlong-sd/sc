<?php
/**
 * datetime: 2023/5/19 3:06
 **/

namespace Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;

/**
 * Js Layer
 *
 * Class Layer
 * @method static Layer open(array $options)
 * @method static Layer photos(array $options)
 * @method static Layer prompt(array $options, JsFunc $yes = null)
 * @method static Layer tab(array $options)
 * @method static Layer alert(string $content, array $options = null, JsFunc $yes = null)
 * @method static Layer confirm(string $content, array $options = null, JsFunc $yes = null, JsFunc $cancel = null)
 * @method static Layer msg(string $content, array $options = null, JsFunc $end = null)
 * @method static Layer load(int|mixed $icon = null, array $options = null)
 * @method static Layer tips(string $content, string $elem, array $options = null)
 * @method static Layer close(int|mixed $index, array $options = null)
 * @method static Layer closeAll(string $type, JsFunc $callback)
 * @method static Layer closeLast(string $type, JsFunc $callback)
 * @method static Layer config(array $options)    全局配置默认属性。
 * @method static Layer ready(JsFunc $callback)    样式初始化就绪。
 * @method static Layer style(int|mixed $index, array $css)    重新设置弹层样式。
 * @method static Layer title(string $title, int|mixed $index)    设置弹层的标题。
 * @method static Layer getChildFrame(string $selector, int|mixed $index) 获取 iframe 页中的元素。
 * @method static Layer getFrameIndex(string $name = 'window.name') 在 iframe 页中获取弹层索引。
 * @method static Layer iframeAuto(int|mixed $index)  设置 iframe 层高度自适应。
 * @method static Layer iframeSrc(int|mixed $index, string $url)  重新设置 iframe 层 URL。
 * @method static Layer setTop(string $var)  将对应弹层的层叠顺序为置顶。
 * @method static Layer full(int|mixed $index)  设置弹层最大化尺寸。
 * @method static Layer min(int|mixed $index)  设置弹层最小化尺寸。
 * @method static Layer restore(int|mixed $index,)  还原弹层尺寸。
 *
 * @package Sc\Util\HtmlStructure\Html\Js
 * @date    2023/5/19
 */
class Layer
{
    private array $config;

    protected bool $isParentOpen = false;

    public function __construct(protected string $type){}

    /**
     * 更新配置
     *
     * @param string $config
     * @param mixed  $value
     *
     * @return Layer
     * @date 2023/5/19
     * @deprecated
     */
    public function config(string $config, mixed $value): static
    {
        $this->config[$config] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return $this
     */
    public function options(string $key, $value): static
    {
        foreach ($this->config as &$value) {
            if (is_array($value)){
                $value[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * @param int $index
     * @param     $value
     *
     * @return $this
     */
    public function paramUpdate(int $index, $value): static
    {
        $this->config[$index] = $value;

        return $this;
    }

    /**
     * 父级打开
     *
     * @return $this
     */
    public function toParent(): static
    {
        $this->isParentOpen = true;

        return $this;
    }

    public function toCode(): string
    {
        return JsFunc::call(sprintf('%slayer.%s', $this->isParentOpen ? 'parent.' : '', $this->type), ...$this->config);
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $layer = new self($name);
        $layer->config = $arguments;
        $layer->parentCheck();
        return $layer;
    }

    private function parentCheck(): void
    {
        foreach ($this->config as $value) {
            if (is_array($value) && !empty($value['parent'])){
                $this->isParentOpen = true;
            }
        }
    }
}