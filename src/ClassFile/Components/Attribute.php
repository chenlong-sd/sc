<?php

namespace Sc\Util\ClassFile\Components;

use Sc\Util\ClassFile\Components\Out\ValueOut;

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
        foreach ($this->params as ['name' => $name, 'value' => $value]) {
            $value = is_array($value)
                ? preg_replace("/ *[\r\n] */", '', ValueOut::out($value, 0))
                : ValueOut::out($value, 0);
            $params[] = ($name ? $name . ': ' : '') . $value;
        }
        $attribute = $params ? ($this->attribute . '(' .implode(', ', $params) . ')') : $this->attribute;

        return sprintf($out, $attribute);
    }

    public function addParam(mixed $value, string $name = null): Attribute
    {
        $this->params[] = compact('name', 'value');

        return $this;
    }
}