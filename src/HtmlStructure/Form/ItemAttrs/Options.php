<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlElement\El;

trait Options
{
    protected array $options = [];
    protected ?string $optionsVarName = null;
    protected array $optionsRemote = [];

    protected ?string $format = null;

    protected array $optionsAttrs = [];

    /**
     * @param array $options
     *
     * @return $this
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * 存储选项变量名字
     *
     * @param string $optionsVarName
     *
     * @return $this
     */
    public function setOptionsVarName(string $optionsVarName): static
    {
        $this->optionsVarName = $optionsVarName;

        return $this;
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        if (count($this->options) === count($this->options, COUNT_RECURSIVE)) {
            return kv_to_form_options($this->options);
        }

        return $this->options;
    }

    /**
     * @param string      $url
     * @param string      $valueCode 获取值的js代码，默认为返回的 data.data值
     * @param string|null $valueName value对应的字段名
     * @param string|null $labelName label对应的字段名
     *
     * @return $this
     */
    public function remoteGetOptions(string $url, string $valueCode = "data.data", string $valueName = null, string $labelName = null): static
    {
        $this->optionsRemote = compact('url', 'valueCode', 'valueName', 'labelName');

        return $this;
    }

    /**
     * @param string|\Stringable $stringable 模板内容， 当前项目变量 item, 例： {{ item.label }}
     *
     * @return $this
     */
    public function format(string|\Stringable $stringable): static
    {
        $this->format = $stringable;

        return $this;
    }

    /**
     * 设置选项属性
     *
     * @param array|string $attr
     * @param mixed        $value
     *
     * @return $this
     */
    public function setOptionsAttrs(array|string $attr, mixed $value = ''): static
    {
        if (!is_array($attr)){
            $attr = $value === '' ? El::getAttrFromStr($attr) : [$attr => $value];
        }

        foreach ($attr as $key => &$value) {
            if (is_bool($value)){
                $value = $value ? 'true' : 'false';
            }
        }
        $this->optionsAttrs = array_merge($this->optionsAttrs, $attr);
        return $this;
    }

    public function getOptionsAttrs(): array
    {
        return $this->optionsAttrs;
    }
}