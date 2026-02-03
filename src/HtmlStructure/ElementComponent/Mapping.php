<?php

namespace Sc\Util\HtmlStructure\ElementComponent;

use Sc\Util\HtmlStructure\Table\ColumnTags;

class Mapping
{
    private string|\Stringable $el = '';
    private array|string $mapping = [];
    private array $attrs = [];

    public function __construct(private readonly string|int    $value,
                                private readonly string $valueKey = 'value',
                                private readonly string $labelKey = 'label')
    {

    }

    public function setEl(string|\Stringable $el): static
    {
        $this->el = $el;
        return $this;
    }

    public function setAttrs(array $attrs): static
    {
        $this->attrs = $attrs;
        return $this;
    }

    public function mapping(array|string|ColumnTags $mapping): static
    {
        $mapping = $mapping instanceof ColumnTags ? $mapping->getTags() : $mapping;
        if (is_array($mapping) && count($mapping) == count($mapping, COUNT_RECURSIVE)){
            $mappings = [];
            foreach ($mapping as $valueKey => $labelKey) {
                $mappings[] = [
                    $this->valueKey => $valueKey,
                    $this->labelKey => $labelKey,
                ];
            }
            $this->mapping = $mappings;
        } else {
            $this->mapping = $mapping;
        }
        return $this;
    }

    public function render()
    {
        if (is_int($this->value) || (is_string($this->value) && str_starts_with($this->value, '@'))) {
            $value = is_int($this->value) ? $this->value : strtr($this->value, ['@' => '']);
        }else{
            $value = "'{$this->value}'";
        }

        if (is_array($this->mapping)){
            $el = h();
            foreach ($this->mapping as $mapping) {
                $format = $this->el ?: h('el-text', $mapping[$this->labelKey], [
                    'v-if' => "$value == {$mapping[$this->valueKey]}",
                ])->setAttrs($this->attrs);
                $el->append($format);
            }
        }else{
            $el = h('template', $this->el ?: "item.{$this->labelKey}", [
                'v-for' => "(item, index) in $this->mapping",
                'v-if' => "$value == item.{$this->valueKey}",
                'key' => "index",
            ])->setAttrs($this->attrs);
        }

        return $el;
    }

    public function __toString(): string
    {
        return  $this->render();
    }
}