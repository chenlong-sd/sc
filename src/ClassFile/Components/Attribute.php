<?php

namespace Sc\Util\ClassFile\Components;

/**
 * Class AttributeOut
 */
class Attribute
{
    private mixed $params = [];

    public function __construct(private readonly mixed $attribute)
    {
    }

    public function out(): string
    {
        $out = "#[%s]";

        $params = [];
        foreach ($this->params as $param) {
            $params[] = ValueOut::out($param, 0);
        }
        $attribute = $params ? ($this->attribute . '(' .implode(', ', $params) . ')') : $this->attribute;

        return sprintf($out, $attribute);
    }

    public function setParams(mixed ...$params): Attribute
    {
        $this->params = $params;
        return $this;
    }
}