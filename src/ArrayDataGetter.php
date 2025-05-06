<?php

namespace Sc\Util;


/**
 * 已知数组利用对象的IDE提示来获取数据
 * 比如配置文件，配置文件可以利用这个特性来获取数据
 * 比如三方接口返回的数据，可以利用这个特性来获取数据
 *
 * Trait ArrayDataGetter
 */
abstract class ArrayDataGetter implements \ArrayAccess
{
    private array $__GetterData;

    protected function setGetterData(array $data): void
    {
        $this->__GetterData = $data;
    }

    public function __get(string $name)
    {
        $data = $this->__GetterData[$name];
        if (is_array($data)) {
            if (!array_is_list($data)) {
                $this->__GetterData[$name] = $this->childrenData($data);
            }else{
                $newData = [];
                foreach ($data as $datum) {
                    $newData[] = is_array($datum) ? $this->childrenData($datum) : $datum;
                }
                $this->__GetterData[$name] = $newData;
            }
        }
        return $this->__GetterData[$name];
    }

    private function childrenData($data): object
    {
        return new class ($data) extends ArrayDataGetter{
            public function __construct(array $data)
            {
                $this->setGetterData($data);
            }
        };
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
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__GetterData[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->__GetterData[$offset]);
    }
}