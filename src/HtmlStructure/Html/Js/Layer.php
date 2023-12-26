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
 *
 * @package Sc\Util\HtmlStructure\Html\Js
 * @date    2023/5/19
 */
class Layer
{
    private array $config;

    /**
     * @param array $config
     *
     * @return Layer
     * @date 2023/5/19
     */
    public static function open(array $config): Layer
    {
        $layer = new self();

        $layer->config = $config;

        return $layer;
    }

    /**
     * 更新配置
     *
     * @param string $config
     * @param mixed  $value
     *
     * @return Layer
     * @date 2023/5/19
     */
    public function config(string $config, mixed $value): static
    {
        $this->config[$config] = $value;

        return $this;
    }

    /**
     * 父级打开
     *
     * @return $this
     */
    public function toParent(): static
    {
        $this->config['parent'] = true;
        return $this;
    }

    public function toCode(): string
    {
        $config = array_map(fn($v) => $v instanceof JsFunc ? $v->toCode() : $v, $this->config);
        $parent = !empty($config['parent']);
        unset($config['parent']);

        $config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // 处理json转化时换行处理
        $config = strtr($config, ['\r\n' => "\r\n"]);

        return Grammar::mark(sprintf("%slayui.layer.open(%s);", $parent ? 'parent.' : '', $config));
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}