<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasPlaceholder
{
    protected string $placeholder = '';

    /**
     * 设置字段占位提示文案。
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string
    {
        if ($this->placeholder !== '') {
            return $this->placeholder;
        }

        return $this->defaultPromptPrefix() . $this->label();
    }

    protected function defaultPromptPrefix(): string
    {
        return '请输入';
    }
}
