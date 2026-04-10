<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasPlaceholder
{
    protected string $placeholder = '';

    /**
     * 设置字段占位提示文案。
     *
     * @param string $placeholder 占位提示文案。
     * @return static 当前字段实例。
     *
     * 示例：
     * `Fields::text('title', '标题')->placeholder('请输入标题')`
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
