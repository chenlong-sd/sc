<?php

namespace Sc\Util\ClassFile\Components\Out;

/**
 * Class RowOut
 */
class RawOut implements \Stringable
{
    public function __construct(private readonly string $value, private readonly string $type = 'classname')
    {
    }

    public function out(): string
    {
        return $this->type === 'classname' ? $this->value . '::class' : $this->value;
    }

    public function __toString(): string
    {
        return $this->out();
    }
}