<?php

namespace Sc\Util\Wechat;

trait ArrayAccessData
{
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function getter(string $key, mixed $default = null)
    {
        return $this->data[$key] ?? $default;
    }


    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getter($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}