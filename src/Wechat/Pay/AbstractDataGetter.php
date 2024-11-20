<?php

namespace Sc\Util\Wechat\Pay;


/**
 * Class DataGetter
 */
abstract class AbstractDataGetter
{
    protected array $data;
    protected array $ObjectMap = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function ObjectMap(): array
    {
        return [];
    }

    public function __get(string $name)
    {
        $objectMap = $this->ObjectMap();
        if (isset($objectMap[$name])) {
            if (!isset($this->ObjectMap[$name])){
                $this->ObjectMap[$name] = new $objectMap[$name]($this->data[$name]);
            }

            return $this->ObjectMap[$name];
        }

        return $this->data[$name];
    }

    public function getData(): array
    {
        return $this->data;
    }
}