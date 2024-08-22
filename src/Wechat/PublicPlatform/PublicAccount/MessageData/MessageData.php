<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class MessageData
 */
abstract class MessageData
{
    public function __construct(private readonly array $data){}

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }
}