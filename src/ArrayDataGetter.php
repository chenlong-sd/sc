<?php

namespace Sc\Util;


use Closure;

/**
 * 已知数组利用对象的IDE提示来获取数据
 * 比如配置文件，配置文件可以利用这个特性来获取数据
 * 比如三方接口返回的数据，可以利用这个特性来获取数据
 *
 * Trait ArrayDataGetter
 */
abstract class ArrayDataGetter implements \ArrayAccess, \Iterator
{
    private array $__GetterData;

    public function __construct(array $data)
    {
        $this->__GetterData = $this->dataConversion($data);
    }

    private function dataConversion(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    $value = $this->dataConversion($value);
                } else {
                    $value = $this->arrayDataGetter($value);
                }
            }
        }

        return $data;
    }

    public function toArray(): array
    {
        foreach ($this->__GetterData as &$value) {
            if (!$value instanceof ArrayDataGetter){
                continue;
            }
            $value = $value->toArray();
        }

        return $this->__GetterData;
    }

    public function __get(string $name)
    {
        return $this->__GetterData[$name];
    }

    private function arrayDataGetter($data): object
    {
        return new class ($data) extends ArrayDataGetter {};
    }

    public function getData(): array
    {
        return $this->__GetterData;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->__GetterData[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__GetterData[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {}

    public function offsetUnset(mixed $offset): void{}

    public function current(): mixed
    {
        return current($this->__GetterData);
    }

    public function next(): void
    {
        next($this->__GetterData);
    }

    public function key(): mixed
    {
        return key($this->__GetterData);
    }

    public function valid(): bool
    {
        return current($this->__GetterData) !== false;
    }

    public function rewind(): void
    {
        reset($this->__GetterData);
    }
}