<?php

namespace Sc\Util\ClassFile\Components;

use Sc\Util\ClassFile\Components\Out\ValueOut;

/**
 * Class AttributeOut
 */
class Attribute
{
    private mixed $params = [];

    public function __construct(private mixed $name)
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
        $attribute = $params ? ($this->name . '(' .implode(', ', $params) . ')') : $this->name;

        return sprintf($out, $attribute);
    }

    public function addParam(mixed $value, string $name = null): Attribute
    {
        $this->params[] = compact('name', 'value');

        return $this;
    }

    public function setName(mixed $name): Attribute
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParamValue($index = 0)
    {
        if (is_int($index) ) {
            return $this->params[$index]['value'];
        }

        $filter = array_filter($this->params, function ($item) use ($index) {
            return $item['name'] === $index;
        });

        return $filter ? current($filter)['value'] : null;
    }

    public function setParam(mixed $value, $index = 0): void
    {
        if (is_int($index)){
            if (isset($this->params[$index])){
                $this->params[$index]['value'] = $value;
            }else{
                $this->addParam($value);
            }
        }else{
            $has = false;
            foreach ($this->params as &$param) {
                if ($param['name'] === $index) {
                    $param['value'] = $value;
                    $has = true;
                }
            }
            if (!$has) {
                $this->addParam($value, $index);
            }
        }
    }
}