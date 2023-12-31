<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait Options
{
    protected array $options = [];
    protected ?string $optionsVarName = null;

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
            $options = [];
            foreach ($this->options as $value => $label) {
                $options[] = ['value' => $value, 'label' => $label];
            }
            return $options;
        }

        return $this->options;
    }
}