<?php
/**
 * datetime: 2023/2/15 0:55
 **/

namespace Sc\Util\Wechat\Pay\Notice;

/**
 * 解析后的data设置到类属性
 *
 * Trait SetData
 *
 * @package Sc\Util\Wechat\Pay\Notice
 * @date    2023/2/15
 */
trait SetData
{
    /**
     * SetData constructor.
     *
     * @param array $data
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2023/2/15
     */
    public function __construct(array $data)
    {
        $this->set($data);
    }

    /**
     * @param array $data
     *
     * @date 2023/2/15
     */
    private function set(array $data): void
    {
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getProperties() as $property){
            $type = $property->getType()->getName();

            if ($property->getType()->isBuiltin()){
                settype($data[$property->getName()], $type);
                $this->{$property->getName()} = $data[$property->getName()];
            }else{
                if (isset($data[$property->getName()])) {
                    $this->{$property->getName()} = new $type($data[$property->getName()]);
                }
            }
        }
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $property => $value) {
            if (is_array($value)) {
                $tempArray = [];

                foreach ($value as $key => $item) {
                    if (is_object($item)) {
                        $tempArray[$key] = $item->toArray();
                    }else{
                        $tempArray[$key] = $value;
                    }
                }
                $array[$property] = $tempArray;
                continue;
            }

            if (is_object($value)) {
                $array[$property] = $value->toArray();
            }else{
                $array[$property] = $value;
            }
        }

        return $array;
    }
}
