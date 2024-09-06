<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait Options
{
    protected array $options = [];
    protected ?string $optionsVarName = null;
    protected array $optionsRemote = [];

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
     * @param string $url
     * @param string $valueCode 获取值的js代码，默认为返回的 data.data值
     *
     * @return $this
     */
    public function remoteGetOptions(string $url, string $valueCode = "data.data"): static
    {
        $this->optionsRemote = compact('url', 'valueCode');

        return $this;
    }
}